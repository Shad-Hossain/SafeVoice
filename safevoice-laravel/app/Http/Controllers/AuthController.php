<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Models\PasswordReset;

class AuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|unique:users,phone',
            'password'  => 'required|min:8',
            'id_type'   => 'required|in:nid,birth_certificate',
            'id_number' => 'required|string|unique:users,id_number',
        ]);

        $cleanId = preg_replace('/\D/', '', $request->id_number);

        if ($request->id_type === 'nid') {
            if (!in_array(strlen($cleanId), [10, 17])) {
                return response()->json(['success' => false, 'message' => 'NID must be 10 or 17 digits.'], 422);
            }
        }
        if ($request->id_type === 'birth_certificate') {
            if (strlen($cleanId) !== 17) {
                return response()->json(['success' => false, 'message' => 'Birth Certificate number must be 17 digits.'], 422);
            }
        }

        $cleanPhone = preg_replace('/\D/', '', $request->phone);
        if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $idDocPath    = null;
        $profilePhoto = null;

        if ($request->hasFile('id_document')) {
            $file = $request->file('id_document');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedExtensions)) {
                return response()->json(['success' => false, 'message' => 'ID document must be JPG, PNG, or PDF.'], 422);
            }
            $filename  = 'id_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads'), $filename);
            $idDocPath = 'uploads/' . $filename;
        }

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return response()->json(['success' => false, 'message' => 'Profile photo must be JPG, PNG, GIF, or WEBP.'], 422);
            }
            $filename     = 'photo_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads'), $filename);
            $profilePhoto = 'uploads/' . $filename;
        }

        $status = $request->id_type === 'birth_certificate' ? 'Pending' : 'Active';

        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            // ✅ email_hash: login lookup এর জন্য — plaintext email ছাড়াও খোঁজা যাবে
            'email_hash'       => hash('sha256', strtolower(trim($request->email))),
            'phone'            => $cleanPhone,
            'password_hash'    => Hash::make($request->password), // bcrypt — safe
            'id_type'          => $request->id_type,
            'id_number'        => $cleanId,
            'id_document_path' => $idDocPath,
            'location'         => $request->location ?? '',
            'profile_photo'    => $profilePhoto,
            'status'           => $status,
        ]);

        if ($status === 'Pending') {
            return response()->json([
                'success' => true,
                'pending' => true,
                'message' => 'Registration submitted! Your birth certificate is being reviewed. You will be able to login once approved (usually within 24 hours).',
            ]);
        }

        // ✅ Sanctum token — cryptographically secure, DB-তে hashed রাখা হয়
        // কেউ token decode করে user_id বা email বের করতে পারবে না
        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'pending' => false,
            'message' => 'Registration successful!',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // ✅ Rate limiting: একই IP থেকে ৫ বার fail হলে ১ মিনিট block
        $ip  = $request->ip();
        $key = 'login:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Try again in {$seconds} seconds.",
            ], 429);
        }

        // ✅ email_hash দিয়ে খোঁজো — plaintext email comparison নেই
        $emailHash = hash('sha256', strtolower(trim($request->email)));
        $user = User::where('email_hash', $emailHash)->first();

        // email_hash না থাকলে (পুরনো records) fallback
        if (!$user) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            RateLimiter::hit($key, 60); // 60 seconds decay
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        // Login সফল হলে rate limit clear করো
        RateLimiter::clear($key);

        if ($user->status === 'Pending') {
            return response()->json([
                'success' => false, 'pending' => true,
                'message' => 'Your account is pending approval. Our team is reviewing your birth certificate. Please wait up to 24 hours.',
            ], 403);
        }

        if ($user->status === 'Banned') {
            return response()->json([
                'success' => false, 'banned' => true,
                'message' => 'Your account has been permanently banned from SafeVoice.',
            ], 403);
        }

        if ($user->status === 'Suspended') {
            $count      = $user->suspension_count ?? 1;
            $ordinal    = ['', '1st', '2nd', '3rd'][$count] ?? "{$count}th";
            $remaining  = 3 - $count;
            $activateOn = $user->suspended_until
                ? \Carbon\Carbon::parse($user->suspended_until)->format('d M Y')
                : 'soon';
            $strikeMsg = $remaining > 0
                ? "{$remaining} more suspension" . ($remaining === 1 ? '' : 's') . " and your account will be permanently banned."
                : "This is your final warning.";

            return response()->json([
                'success' => false, 'suspended' => true,
                'strike' => $count, 'strike_ordinal' => $ordinal,
                'activation_date' => $activateOn, 'remaining' => $remaining,
                'message' => "Your account has been suspended.\n\nThis is your {$ordinal} suspension ({$count}/3).\nReactivation date: {$activateOn}.\n\n{$strikeMsg}",
            ], 403);
        }

        // ✅ পুরনো সব token delete করো (আগের device এ login থাকলে logout হবে)
        // একাধিক device support চাইলে এই line টা বাদ দাও
        $user->tokens()->delete();

        // ✅ নতুন Sanctum token বানাও
        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'token'   => $token,
            'user'    => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'status'           => $user->status,
                'profile_photo'    => $user->profile_photo,
                'complaints_count' => $user->complaints_count,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        // ✅ শুধু এই device এর token delete
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        $request->session()->flush();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    // POST /api/forget_password
    public function forgotPassword(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'send_otp') {
            $request->validate(['email' => 'required|email']);
            $email = strtolower(trim($request->email));

            // ✅ email_hash দিয়ে খোঁজো
            $emailHash = hash('sha256', $email);
            $user = User::where('email_hash', $emailHash)->orWhere('email', $email)->first();

            if (!$user) {
                // ✅ user না পেলেও same response — attacker জানতে পারবে না কোন email registered
                return response()->json(['success' => true, 'message' => 'If this email is registered, an OTP has been sent.']);
            }

            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            PasswordReset::where('email', $email)->delete();
            PasswordReset::create([
                'phone'      => $user->phone ?? '',
                'email'      => $email,
                'otp_code'   => Hash::make($otp), // ✅ OTP টাও hash করে রাখো
                'expires_at' => now()->addMinutes(10),
                'used'       => false,
            ]);

            $sent = $this->sendOtpEmail($email, $user->name, $otp);
            if (!$sent['success']) {
                return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 500);
            }

            return response()->json(['success' => true, 'message' => 'If this email is registered, an OTP has been sent.']);
        }

        if ($action === 'verify_otp') {
            $request->validate(['email' => 'required|email', 'otp' => 'required|string|size:6']);
            $email = strtolower(trim($request->email));

            $record = PasswordReset::where('email', $email)
                ->where('used', false)
                ->where('expires_at', '>=', now())
                ->first();

            // ✅ hashed OTP verify করো
            if (!$record || !Hash::check($request->otp, $record->otp_code)) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'OTP verified.']);
        }

        if ($action === 'reset') {
            $request->validate([
                'email'        => 'required|email',
                'otp'          => 'required|string|size:6',
                'new_password' => 'required|min:8',
            ]);
            $email = strtolower(trim($request->email));

            $record = PasswordReset::where('email', $email)
                ->where('used', false)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$record || !Hash::check($request->otp, $record->otp_code)) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
            }

            $emailHash = hash('sha256', $email);
            $user = User::where('email_hash', $emailHash)->orWhere('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
            }

            $user->update(['password_hash' => Hash::make($request->new_password)]);
            $record->update(['used' => true]);

            // ✅ password reset হলে সব token revoke করো (security)
            $user->tokens()->delete();

            return response()->json(['success' => true, 'message' => 'Password reset successfully. Please login again.']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
    }

    private function sendOtpEmail(string $email, string $name, string $otp): array
    {
        try {
            $mailerPath = base_path('PHPMailer-master/src');
            require_once $mailerPath . '/Exception.php';
            require_once $mailerPath . '/PHPMailer.php';
            require_once $mailerPath . '/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME', '');
            $mail->Password   = env('MAIL_PASSWORD', '');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = env('MAIL_PORT', 587);
            $mail->setFrom(env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', '')), env('MAIL_FROM_NAME', 'SafeVoice'));
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'SafeVoice — Your Password Reset OTP';
            $mail->Body    = $this->otpEmailTemplate($name, $otp);
            $mail->AltBody = "Dear $name,\n\nYour OTP: $otp\n\nValid for 10 minutes.\n\n— SafeVoice";
            $mail->send();
            return ['success' => true];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP email failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function otpEmailTemplate(string $name, string $otp): string
    {
        $digits = implode('', array_map(
            fn($d) => "<span style='display:inline-block;width:44px;height:52px;line-height:52px;text-align:center;font-size:26px;font-weight:700;background:#0d1526;border:2px solid #4f9eff;border-radius:10px;color:#fff;margin:0 4px;'>$d</span>",
            str_split($otp)
        ));

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:560px;">
<tr><td style="background:linear-gradient(135deg,#1a3a6e,#0d1f42);padding:32px;text-align:center;">
  <div style="font-size:28px;margin-bottom:8px;">🛡️</div>
  <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">SafeVoice</h1>
  <p style="color:#a0b4cc;margin:6px 0 0;font-size:13px;">Password Reset Request</p>
</td></tr>
<tr><td style="padding:36px 40px;">
  <p style="color:#a0b4cc;font-size:15px;margin:0 0 10px;">Hello, <strong style="color:#fff;">$name</strong></p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:0 0 28px;">Your OTP is valid for <strong style="color:#4f9eff;">10 minutes</strong>. Do not share it with anyone.</p>
  <div style="text-align:center;margin:0 0 28px;">$digits</div>
  <div style="background:#0a0f1e;border-left:4px solid #f39c12;border-radius:8px;padding:14px 18px;">
    <p style="color:#f39c12;font-size:13px;margin:0;font-weight:600;">⚠️ Never share this OTP with anyone, including SafeVoice staff.</p>
  </div>
</td></tr>
<tr><td style="border-top:1px solid #1e2d4a;padding:20px 40px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice. All rights reserved.</p>
</td></tr>
</table></td></tr></table>
</body></html>
HTML;
    }

    // GET /api/check-session
    public function checkSession(Request $request)
    {
        // ✅ Sanctum token থেকে user নাও
        $user = $request->user();
        if ($user) {
            return response()->json([
                'success'  => true,
                'loggedIn' => true,
                'user'     => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        // Legacy session fallback
        if ($request->session()->has('user_id')) {
            return response()->json([
                'success'  => true,
                'loggedIn' => true,
                'user'     => [
                    'id'    => $request->session()->get('user_id'),
                    'name'  => $request->session()->get('user_name'),
                    'email' => $request->session()->get('user_email'),
                ],
            ]);
        }

        return response()->json(['success' => false, 'loggedIn' => false], 401);
    }
    // GET /api/profile — settings এ NID দেখানোর জন্য
    public function getProfile(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            try { $userId = $request->user()?->id; } catch (\Exception $e) {}
        }
        if (!$userId) $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['success' => false], 401);

        $user = \App\Models\User::find($userId);
        if (!$user) return response()->json(['success' => false], 404);

        return response()->json([
            'success' => true,
            'user' => [
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'id_number' => $user->id_number,
                'id_type'   => $user->id_type,
            ],
        ]);
    }

    // POST /api/profile/update — phone ও email update
    public function updateProfile(Request $request)
    {
        // user_id server থেকে
        $userId = null;
        try { $userId = $request->user()?->id; } catch (\Exception $e) {}
        if (!$userId) $userId = $request->session()->get('user_id');
        if (!$userId) $userId = $request->input('user_id');
        if (!$userId) return response()->json(['success' => false, 'message' => 'Please login first.'], 401);

        $user = \App\Models\User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        $updates = [];
        $message = [];

        // Phone update
        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', $request->phone);
            if (strlen($phone) === 13) $phone = substr($phone, 2);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                return response()->json(['success' => false, 'message' => 'Invalid phone number.'], 422);
            }
            // Duplicate check
            if (\App\Models\User::where('phone', $phone)->where('id', '!=', $userId)->exists()) {
                return response()->json(['success' => false, 'message' => 'This phone number is already registered.'], 422);
            }
            $updates['phone'] = $phone;
            $message[] = 'Phone updated.';
        }

        // Email update
        if ($request->filled('email')) {
            $email = strtolower(trim($request->email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'message' => 'Invalid email address.'], 422);
            }
            // Duplicate check
            $emailHash = hash('sha256', $email);
            if (\App\Models\User::where('email_hash', $emailHash)->where('id', '!=', $userId)->exists()) {
                return response()->json(['success' => false, 'message' => 'This email is already registered.'], 422);
            }
            $updates['email']      = $email;
            $updates['email_hash'] = $emailHash;
            $message[] = 'Email updated.';

            // ✅ Confirmation email পাঠাও
            $this->sendProfileUpdateEmail($email, $user->name);
        }

        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'Nothing to update.'], 422);
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'message' => implode(' ', $message) . (isset($updates['email']) ? ' একটি confirmation email পাঠানো হয়েছে।' : ''),
            'email'   => $updates['email']   ?? $user->email,
            'phone'   => $updates['phone']   ?? $user->phone,
        ]);
    }

    private function sendProfileUpdateEmail(string $email, string $name): void
    {
        try {
            $mailerPath = base_path('PHPMailer-master/src');
            require_once $mailerPath . '/Exception.php';
            require_once $mailerPath . '/PHPMailer.php';
            require_once $mailerPath . '/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME', '');
            $mail->Password   = env('MAIL_PASSWORD', '');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = env('MAIL_PORT', 587);
            $mail->setFrom(env('MAIL_FROM_ADDRESS', ''), env('MAIL_FROM_NAME', 'SafeVoice'));
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'SafeVoice — Email Address Updated';
            $mail->Body    = "<p>Hi <strong>{$name}</strong>,</p><p>Your email address on SafeVoice has been updated to <strong>{$email}</strong>.</p><p>If you did not make this change, please contact us immediately at support@safevoice.com.</p><p>— SafeVoice Team</p>";
            $mail->AltBody = "Hi {$name},\n\nYour SafeVoice email has been updated to {$email}.\n\nIf you did not do this, contact support@safevoice.com immediately.\n\n— SafeVoice";
            $mail->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Profile update email failed: ' . $e->getMessage());
        }
    }

}