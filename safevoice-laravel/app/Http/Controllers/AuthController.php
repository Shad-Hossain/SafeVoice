<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'id_number' => 'required|string',
        ]);

        $cleanPhone = preg_replace('/\D/', '', $request->phone);
        if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);

        $idDocPath    = null;
        $profilePhoto = null;

        if ($request->hasFile('id_document')) {
            $file      = $request->file('id_document');
            $filename  = 'id_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $idDocPath = 'uploads/' . $filename;
        }

        if ($request->hasFile('profile_photo')) {
            $file      = $request->file('profile_photo');
            $filename  = 'photo_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $profilePhoto = 'uploads/' . $filename;
        }

        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'phone'            => $cleanPhone,
            'password_hash'    => Hash::make($request->password),
            'id_type'          => $request->id_type,
            'id_number'        => $request->id_number,
            'id_document_path' => $idDocPath,
            'location'         => $request->location ?? '',
            'profile_photo'    => $profilePhoto,
        ]);

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'success' => true,
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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        if ($user->status === 'Banned') {
            return response()->json(['success' => false, 'message' => 'Your account has been banned.'], 403);
        }

        if ($user->status === 'Suspended') {
            return response()->json(['success' => false, 'message' => 'Your account is suspended.'], 403);
        }

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        $request->session()->put('user_id',    $user->id);
        $request->session()->put('user_name',  $user->name);
        $request->session()->put('user_email', $user->email);

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
        $request->session()->flush();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    // POST /api/forget_password
    public function forgotPassword(Request $request)
    {
        $action = $request->input('action');

        // ── STEP 1: OTP পাঠাও ───────────────────────────────────
        if ($action === 'send_otp') {
            $request->validate(['email' => 'required|email']);
            $email = strtolower(trim($request->email));

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No account found with this email.'], 404);
            }

            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            PasswordReset::where('email', $email)->delete();
            PasswordReset::create([
                'phone'      => $user->phone ?? '',
                'email'      => $email,
                'otp_code'   => $otp,
                'expires_at' => now()->addMinutes(10),
                'used'       => false,
            ]);

            $sent = $this->sendOtpEmail($email, $user->name, $otp);

            if (!$sent['success']) {
                return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . $sent['error']], 500);
            }

            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        // ── STEP 2: OTP verify করো ──────────────────────────────
        if ($action === 'verify_otp') {
            $request->validate([
                'email' => 'required|email',
                'otp'   => 'required|string|size:6',
            ]);
            $email = strtolower(trim($request->email));

            $record = PasswordReset::where('email', $email)
                ->where('otp_code', $request->otp)
                ->where('used', false)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'OTP verified.']);
        }

        // ── STEP 3: Password reset করো ──────────────────────────
        if ($action === 'reset') {
            $request->validate([
                'email'        => 'required|email',
                'otp'          => 'required|string|size:6',
                'new_password' => 'required|min:8',
            ]);
            $email = strtolower(trim($request->email));

            $record = PasswordReset::where('email', $email)
                ->where('otp_code', $request->otp)
                ->where('used', false)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OTP. Please restart.'], 422);
            }

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            $user->update(['password_hash' => Hash::make($request->new_password)]);
            $record->update(['used' => true]);

            return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
    }

    // ── PHPMailer দিয়ে OTP email পাঠাও ─────────────────────────
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
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice</p>
</td></tr>
</table></td></tr></table>
</body></html>
HTML;
    }

    // GET /api/check-session
    public function checkSession(Request $request)
    {
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
}