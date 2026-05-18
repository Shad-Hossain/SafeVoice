<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\PrivateInvestigator;
use App\Models\Complaint;
use App\Models\PiNotification;
use App\Models\PiPayment;
use App\Models\User;
use App\Models\ComplaintEvidence;

class PrivateInvestigatorController extends Controller
{
    // GET /api/pi
    public function index()
    {
        $pis = PrivateInvestigator::orderBy('pi_code')->get();
        return response()->json(['success' => true, 'investigators' => $pis]);
    }

    // POST /api/add_pi
    public function store(Request $request)
    {
        $request->validate([
            'full_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:private_investigators,email',
            'phone'      => 'required|string',
            'address'    => 'required|string',
            'nid_number' => 'required|string|unique:private_investigators,nid_number',
        ]);

        $last   = PrivateInvestigator::orderByDesc('id')->first();
        $nextNo = $last ? (intval(substr($last->pi_code, 2)) + 1) : 1;
        $piCode = 'PI' . str_pad($nextNo, 3, '0', STR_PAD_LEFT);

        $pi = PrivateInvestigator::create([
            'pi_code'      => $piCode,
            'full_name'    => $request->full_name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'address'      => $request->address,
            'nid_number'   => $request->nid_number,
            'login_email'  => $request->email,
            'is_active'    => true,
            'active_cases' => 0,
            'total_cases'  => 0,
            'notes'        => $request->notes ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "PI {$piCode} added successfully.",
            'pi'      => $pi,
        ]);
    }

    // POST /api/pi/notify — admin sends notification to user
    public function sendNotification(Request $request)
    {
        $request->validate(['complaint_id' => 'required|string']);

        $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
        if (!$complaint) return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);

        $deadline = now()->addDays(3);

        $complaint->update([
            'status'           => 'PI Notification Sent',
            'pi_notified_at'   => now(),
            'payment_deadline' => $deadline,
        ]);

        PiNotification::updateOrCreate(
            ['complaint_id' => $request->complaint_id],
            ['user_id' => $complaint->user_id, 'status' => 'sent', 'sent_at' => now()]
        );

        return response()->json([
            'success'  => true,
            'message'  => 'Notification sent. User has 3 days to pay.',
            'deadline' => $deadline->toDateTimeString(),
        ]);
    }

    // GET /api/pi/notifications — user sees their pending PI notifications
    public function notifications(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $complaints = Complaint::where('user_id', $userId)
            ->where('status', 'PI Notification Sent')
            ->get(['complaint_id','type','location','status','payment_deadline','submitted_at']);

        return response()->json(['success' => true, 'notifications' => $complaints]);
    }

    // POST /api/pi/payment — auto confirm, auto assign PI, email both parties
    public function payment(Request $request)
    {
        $request->validate([
            'complaint_id'   => 'required|string',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank',
            'txn_id'         => 'required|string',
            'sender_number'  => 'nullable|string',
        ]);

        $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
        if (!$complaint) return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);

        if ($complaint->payment_deadline && now()->isAfter($complaint->payment_deadline)) {
            return response()->json(['success' => false, 'message' => 'Payment deadline has passed.'], 422);
        }

        if (PiPayment::where('txn_id', $request->txn_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Transaction ID already used.'], 422);
        }

       $userId = $request->session()->get('user_id')
    ?? $request->input('user_id')
    ?? $complaint->user_id;

        // Save as confirmed immediately — no admin step needed
        PiPayment::create([
            'complaint_id'   => $request->complaint_id,
            'user_id'        => $userId,
            'payment_method' => $request->payment_method,
            'sender_number'  => $request->sender_number,
            'txn_id'         => $request->txn_id,
            'amount'         => 1000.00,
            'status'         => 'confirmed',
            'confirmed_at'   => now(),
        ]);

        // Auto-assign PI with lowest workload (max 10 active cases)
        $pi = PrivateInvestigator::where('is_active', true)
            ->where('active_cases', '<', 10)
            ->orderBy('active_cases')
            ->first();

        if (!$pi) {
            // সব PI এর case 10 — payment hold করো, admin manually assign করবে
            $complaint->update(['status' => 'PI Payment Pending Confirmation']);
            return response()->json([
                'success' => true,
                'message' => 'Payment received. All investigators are at full capacity. Admin will assign manually.',
            ]);
        }

        $complaint->update([
            'assigned_pi_id' => $pi->id,
            'pi_assigned_at' => now(),
            'status'         => 'Private Investigator Assigned',
        ]);

        $pi->increment('active_cases');
        $pi->increment('total_cases');

        PiNotification::where('complaint_id', $request->complaint_id)
            ->update(['status' => 'payment_confirmed', 'responded_at' => now()]);

        // Email PI with full case details
        $this->sendPiAssignmentEmail($pi, $complaint);

        // Email User with confirmation
        $this->sendUserConfirmationEmail($complaint, $pi, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed! PI assigned. Check your email for details.',
            'pi_code' => $pi->pi_code,
        ]);
    }

    // GET /api/admin/payments — admin sees all payments (case ID + TXN only, no user info)
    public function pendingPayments()
    {
        $payments = PiPayment::orderByDesc('initiated_at')
            ->get(['id','complaint_id','payment_method','txn_id','amount','status','initiated_at','confirmed_at']);
        return response()->json(['success' => true, 'payments' => $payments]);
    }

    // POST /api/admin/payments/confirm — admin confirms, system auto-assigns PI
    public function confirmPayment(Request $request)
    {
        $request->validate(['payment_id' => 'required|integer']);

        $payment = PiPayment::find($request->payment_id);
        if (!$payment) return response()->json(['success' => false, 'message' => 'Payment not found'], 404);

        $payment->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        // Auto-assign PI with lowest active_cases (max 10)
        $pi = PrivateInvestigator::where('is_active', true)
            ->where('active_cases', '<', 10)
            ->orderBy('active_cases')
            ->first();

        if (!$pi) return response()->json(['success' => false, 'message' => 'All investigators are at full capacity (10 cases each). Please resolve some cases first.'], 422);

        $complaint = Complaint::where('complaint_id', $payment->complaint_id)->first();
        if (!$complaint) return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);

        $complaint->update([
            'assigned_pi_id' => $pi->id,
            'pi_assigned_at' => now(),
            'status'         => 'Private Investigator Assigned',
        ]);

        $pi->increment('active_cases');
        $pi->increment('total_cases');

        PiNotification::where('complaint_id', $payment->complaint_id)
            ->update(['status' => 'payment_confirmed', 'responded_at' => now()]);

        $emailResult = $this->sendPiAssignmentEmail($pi, $complaint);

        return response()->json([
            'success'    => true,
            'message'    => "PI {$pi->pi_code} ({$pi->full_name}) assigned.",
            'pi'         => ['code' => $pi->pi_code, 'name' => $pi->full_name],
            'email_sent' => $emailResult['success'],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $pi = PrivateInvestigator::findOrFail($request->id);
        $pi->update(array_filter([
            'full_name'    => $request->full_name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'nid_number'   => $request->nid_number,
            'address'      => $request->address,
            'photo_url'    => $request->photo_url,
            'nid_photo_url'=> $request->nid_photo_url,
            'notes'        => $request->notes,
        ], fn($v) => $v !== null));
        return response()->json(['success' => true, 'message' => 'PI updated.', 'pi' => $pi]);
    }

    public function toggle(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $pi = PrivateInvestigator::findOrFail($request->id);
        $pi->update(['is_active' => !$pi->is_active]);
        return response()->json(['success' => true, 'is_active' => $pi->is_active]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        PrivateInvestigator::findOrFail($request->id)->delete();
        return response()->json(['success' => true, 'message' => 'PI removed.']);
    }

    public function changePassword(Request $request)
    {
        $request->validate(['id' => 'required|integer', 'new_password' => 'required|min:8']);
        $pi = PrivateInvestigator::findOrFail($request->id);
        $pi->update(['password' => \Illuminate\Support\Facades\Hash::make($request->new_password)]);
        return response()->json(['success' => true, 'message' => 'Password updated.']);
    }

    public function rejectPayment(Request $request)
{
    $request->validate(['complaint_id' => 'required|string']);

    $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
    if (!$complaint) return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);

    $deadlinePassed = $complaint->payment_deadline && now()->isAfter($complaint->payment_deadline);

    PiNotification::where('complaint_id', $request->complaint_id)
        ->update(['status' => 'dismissed', 'responded_at' => now()]);

    $newStatus = $deadlinePassed ? 'Rejected' : 'PI Payment Pending';
    $complaint->update(['status' => $newStatus]);   // ← এই line টা আছে কিনা check করুন

    return response()->json([
        'success'  => true,
        'message'  => $deadlinePassed
            ? 'Payment deadline passed. Complaint rejected.'
            : 'Noted. You can still pay before the deadline.',
        'status'   => $newStatus,
        'deadline' => $complaint->payment_deadline,
    ]);
}

    // PHPMailer helper
    private function sendPiAssignmentEmail(PrivateInvestigator $pi, Complaint $complaint): array
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
            $mail->setFrom(env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', '')), 'SafeVoice System');
            $mail->addAddress($pi->email, $pi->full_name);
            $mail->isHTML(true);
            $mail->Subject = "SafeVoice — New Case Assigned: {$complaint->complaint_id}";
            $mail->Body    = $this->piEmailTemplate($pi, $complaint);
            $mail->AltBody = $this->piEmailPlainText($pi, $complaint);
            $mail->send();

            return ['success' => true];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PI email failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function piEmailTemplate(PrivateInvestigator $pi, Complaint $complaint): string
    {
        $type      = ucfirst(str_replace('_', ' ', $complaint->type));
        $location  = $complaint->location ?: '—';
        $date      = $complaint->incident_date ? date('d M Y, H:i', strtotime($complaint->incident_date)) : '—';
        $submitted = date('d M Y, H:i', strtotime($complaint->submitted_at));
        $desc      = nl2br(htmlspecialchars($complaint->description ?? '—'));

        $victimRows = '';
        if (!$complaint->is_anonymous && $complaint->user_id) {
            $user = User::find($complaint->user_id);
            if ($user) {
                $victimRows = "
                <tr><td style='padding:10px 0;border-bottom:1px solid #1e2d4a;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Name</td>
                        <td style='color:#fff;font-size:14px;'>{$user->name}</td>
                    </tr></table></td></tr>
                <tr><td style='padding:10px 0;border-bottom:1px solid #1e2d4a;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Phone</td>
                        <td style='color:#fff;font-size:14px;'>{$user->phone}</td>
                    </tr></table></td></tr>
                <tr><td style='padding:10px 0;border-bottom:1px solid #1e2d4a;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Email</td>
                        <td style='color:#4f9eff;font-size:14px;'>{$user->email}</td>
                    </tr></table></td></tr>";
            }
        } else {
            $victimRows = "
                <tr><td style='padding:10px 0;border-bottom:1px solid #1e2d4a;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Identity</td>
                        <td style='color:#fbbf24;font-size:14px;font-weight:700;'>⚠️ Anonymous — victim will contact you</td>
                    </tr></table></td></tr>";
        }

        // Evidence files section
        $evidenceFiles = ComplaintEvidence::where('complaint_id', $complaint->complaint_id)->get();
        $evidenceSection = '';
        if ($evidenceFiles->isNotEmpty()) {
            $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
            $fileRows = $evidenceFiles->map(function($f) use ($appUrl) {
                $isPdf = strtolower(substr($f->file_name, -4)) === '.pdf';
                $icon  = $isPdf ? '📄' : '🖼️';
                $url   = $appUrl . '/' . ltrim($f->file_path, '/');
                return "<tr><td style='padding:8px 0;border-bottom:1px solid #1e2d4a;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;width:24px;'>{$icon}</td>
                        <td style='color:#fff;font-size:13px;'>" . htmlspecialchars($f->file_name) . "</td>
                        <td style='text-align:right;'>
                            <a href='{$url}' style='color:#4f9eff;font-size:12px;font-weight:600;text-decoration:none;background:#1e2d4a;border:1px solid #4f9eff40;padding:4px 10px;border-radius:6px;'>View</a>
                        </td>
                    </tr></table>
                </td></tr>";
            })->join('');

            $evidenceSection = "
<tr><td style='padding:0 32px 20px;'>
  <div style='background:#070d1a;border:1px solid #1e2d4a;border-radius:12px;padding:20px 24px;'>
    <div style='font-size:12px;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:14px;'>📎 Evidence Files ({$evidenceFiles->count()})</div>
    <table width='100%' cellpadding='0' cellspacing='0'>
      {$fileRows}
    </table>
  </div>
</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:600px;">
<tr><td style="background:linear-gradient(135deg,#1a3a6e,#0d1f42);padding:28px 32px;border-radius:16px 16px 0 0;">
  <table width="100%"><tr>
    <td><div style="font-size:26px;margin-bottom:4px;">🛡️</div>
      <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">SafeVoice</h1>
      <p style="color:#a0b4cc;margin:4px 0 0;font-size:13px;">New Case Assigned</p>
    </td>
    <td style="text-align:right;vertical-align:top;">
      <div style="background:#a855f720;border:1px solid #a855f740;border-radius:10px;padding:10px 16px;display:inline-block;">
        <div style="color:#c084fc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Your PI Code</div>
        <div style="color:#fff;font-size:22px;font-weight:800;font-family:monospace;">{$pi->pi_code}</div>
      </div>
    </td>
  </tr></table>
</td></tr>
<tr><td style="padding:24px 32px 0;">
  <p style="color:#a0b4cc;font-size:15px;margin:0;">Dear <strong style="color:#fff;">{$pi->full_name}</strong>,</p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:10px 0 0;">
    A new case has been assigned to you. Please contact the victim within
    <strong style="color:#4f9eff;">48 hours</strong>.
  </p>
</td></tr>
<tr><td style="padding:20px 32px;">
  <div style="background:#4f9eff10;border:1px solid #4f9eff40;border-radius:10px;padding:16px 20px;text-align:center;">
    <div style="font-size:11px;color:#a0b4cc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">Case ID</div>
    <div style="font-size:24px;font-weight:800;color:#4f9eff;font-family:monospace;">{$complaint->complaint_id}</div>
    <div style="font-size:12px;color:#a0b4cc;margin-top:4px;">Submitted: {$submitted}</div>
  </div>
</td></tr>
<tr><td style="padding:0 32px 20px;">
  <div style="background:#070d1a;border:1px solid #1e2d4a;border-radius:12px;padding:20px 24px;">
    <div style="font-size:12px;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:14px;">📋 Case Details</div>
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td style="padding:10px 0;border-bottom:1px solid #1e2d4a;">
        <table width="100%"><tr>
          <td style="color:#a0b4cc;font-size:13px;font-weight:600;width:140px;">Incident Type</td>
          <td style="color:#fff;font-size:14px;font-weight:700;">{$type}</td>
        </tr></table></td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #1e2d4a;">
        <table width="100%"><tr>
          <td style="color:#a0b4cc;font-size:13px;font-weight:600;width:140px;">Location</td>
          <td style="color:#fff;font-size:14px;">{$location}</td>
        </tr></table></td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #1e2d4a;">
        <table width="100%"><tr>
          <td style="color:#a0b4cc;font-size:13px;font-weight:600;width:140px;">Incident Date</td>
          <td style="color:#fff;font-size:14px;">{$date}</td>
        </tr></table></td></tr>
      {$victimRows}
    </table>
  </div>
</td></tr>
<tr><td style="padding:0 32px 20px;">
  <div style="background:#070d1a;border:1px solid #1e2d4a;border-radius:12px;padding:20px 24px;">
    <div style="font-size:12px;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:12px;">📝 Description</div>
    <p style="color:#a0b4cc;font-size:14px;line-height:1.8;margin:0;">{$desc}</p>
  </div>
</td></tr>
{$evidenceSection}
<tr><td style="padding:0 32px 24px;">
  <div style="background:#f59e0b10;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 18px;">
    <p style="color:#fbbf24;font-size:13px;margin:0;font-weight:600;">
      ⚠️ Do not share case details externally. Report updates to Super Admin only.
    </p>
  </div>
</td></tr>
<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice · Sent to {$pi->email}</p>
  <p style="color:#3a4a5e;font-size:11px;margin:6px 0 0;">Do not reply. Contact your Super Admin for support.</p>
</td></tr>
</table></td></tr></table>
</body></html>
HTML;
    }

    private function piEmailPlainText(PrivateInvestigator $pi, Complaint $complaint): string
    {
        $type = ucfirst(str_replace('_', ' ', $complaint->type));
        return "Dear {$pi->full_name} ({$pi->pi_code}),\n\n"
            . "New case assigned: {$complaint->complaint_id}\n"
            . "Type: {$type}\n"
            . "Location: {$complaint->location}\n"
            . "Description: {$complaint->description}\n\n"
            . "Contact the victim within 48 hours.\n\n— SafeVoice System";
    }

    // Send confirmation email to user after PI assigned
    private function sendUserConfirmationEmail(Complaint $complaint, PrivateInvestigator $pi, $userId): void
    {
        if (!$userId) return;
        $user = User::find($userId);
        if (!$user || !$user->email) return;

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
            $mail->setFrom(env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', '')), 'SafeVoice System');
            $mail->addAddress($user->email, $user->name);
            $mail->isHTML(true);
            $mail->Subject = "SafeVoice — PI Assigned for {$complaint->complaint_id}";

            $type = ucfirst(str_replace('_', ' ', $complaint->type));
            $mail->Body = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:560px;">
<tr><td style="background:linear-gradient(135deg,#1a3a6e,#0d1f42);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center;">
  <div style="font-size:28px;margin-bottom:6px;">🛡️</div>
  <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">SafeVoice</h1>
  <p style="color:#a0b4cc;margin:4px 0 0;font-size:13px;">Private Investigator Assigned</p>
</td></tr>
<tr><td style="padding:28px 32px 0;">
  <p style="color:#a0b4cc;font-size:15px;margin:0 0 10px;">Dear <strong style="color:#fff;">{$user->name}</strong>,</p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:0 0 20px;">
    Your payment has been received and a <strong style="color:#c084fc;">Private Investigator</strong>
    has been assigned to your case. They will contact you directly on your registered email.
  </p>
</td></tr>
<tr><td style="padding:0 32px 20px;">
  <div style="background:#a855f710;border:1px solid #a855f740;border-radius:12px;padding:18px 22px;text-align:center;">
    <div style="font-size:11px;color:#a0b4cc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Your Case ID</div>
    <div style="font-size:22px;font-weight:800;color:#4f9eff;font-family:monospace;">{$complaint->complaint_id}</div>
    <div style="font-size:13px;color:#a0b4cc;margin-top:6px;">Type: {$type}</div>
  </div>
</td></tr>
<tr><td style="padding:0 32px 24px;">
  <div style="background:#2ecc7110;border-left:4px solid #2ecc71;border-radius:8px;padding:14px 18px;">
    <p style="color:#2ecc71;font-size:13px;margin:0;font-weight:600;">
      ✅ Your PI will reach out to you via email. Please check your inbox regularly.
    </p>
  </div>
</td></tr>
<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice · Protecting voices, securing futures.</p>
</td></tr>
</table></td></tr></table>
</body></html>
HTML;
            $mail->AltBody = "Dear {$user->name}, your PI has been assigned for case {$complaint->complaint_id}. They will contact you via email.";
            $mail->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('User confirmation email failed: ' . $e->getMessage());
        }
    }
}
