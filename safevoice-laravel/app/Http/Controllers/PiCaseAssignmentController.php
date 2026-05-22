<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Complaint;
use App\Models\PrivateInvestigator;
use App\Models\PiCaseAssignment;
use App\Models\User;
use App\Models\SuperAdmin;
use App\Models\ComplaintEvidence;
use App\Models\PiPayment;
use App\Models\UserNotification;
use App\Models\SuperAdminNotification;
/**
 * PI Case Assignment Controller
 * ─────────────────────────────
 * এই controller এর কাজ:
 * 1. sendToNextPi()       — সবচেয়ে কম workload এর PI কে case mail করা
 * 2. handleEmailAction()  — PI মেইলের accept/reject লিংকে ক্লিক করলে এই method চলবে
 *
 * Flow:
 *   Payment confirmed → sendToNextPi()
 *       → PI accept করলে → user কে mail, complaint status update
 *       → PI reject করলে → আবার sendToNextPi() (পরের PI তে)
 *       → সবাই reject → Super Admin কে notify
 */
class PiCaseAssignmentController extends Controller
{
    // Token কত ঘণ্টা valid থাকবে
    const TOKEN_HOURS = 48;

    // ─────────────────────────────────────────────────────────────
    // PUBLIC: Payment confirm হওয়ার পর এই method call করতে হবে
    // ─────────────────────────────────────────────────────────────

    /**
     * সবচেয়ে কম workload এর available PI কে mail পাঠাও।
     * যদি আগে কেউ reject করে থাকে তাদের skip করো।
     *
     * @param  string  $complaintId
     * @return array   ['success' => bool, 'message' => string]
     */
    public function sendToNextPi(string $complaintId): array
    {
        $complaint = Complaint::where('complaint_id', $complaintId)->first();
        if (!$complaint) {
            return ['success' => false, 'message' => 'Complaint not found'];
        }

        // কারা আগে reject করেছে বা mail পেয়েছে
        $alreadyTriedIds = PiCaseAssignment::where('complaint_id', $complaintId)
            ->pluck('pi_id')
            ->toArray();

        // সবচেয়ে কম active_cases এর PI যাকে এখনো try করা হয়নি
        $pi = PrivateInvestigator::where('is_active', true)
            ->where('active_cases', '<', 10)
            ->whereNotIn('id', $alreadyTriedIds)
            ->orderBy('active_cases')        // কম workload আগে
            ->orderBy('id')                  // tie-break: পুরনো PI আগে
            ->first();

        if (!$pi) {
            // সব PI trial শেষ — Super Admin কে notify করো
            $this->notifySuperAdminAllRejected($complaint);
 
            // User কে refund notification পাঠাও
            $this->sendUserRefundEmail($complaint);
 
            // Payment status refunded এ update করো
            PiPayment::where('complaint_id', $complaintId)
                ->where('status', 'confirmed')
                ->update(['status' => 'refunded']);
 
            $complaint->update([
                'status'              => 'PI Assignment Failed',
                'current_pi_email_id' => null,
            ]);

            // ── In-app notification: user কে জানাও refund আসছে ──
            if ($complaint->user_id) {
                UserNotification::notify(
                    $complaint->user_id,
                    'refund_initiated',
                    '💰 Refund Processing',
                    "দুঃখিত! Complaint {$complaint->complaint_id} এর জন্য কোনো PI পাওয়া যায়নি। তোমার payment ৩-৫ কার্যদিবসের মধ্যে refund হবে।",
                    [
                        'complaint_id' => $complaint->complaint_id,
                        'action_url'   => '/dashboard',
                        'icon'         => '💰',
                    ]
                );
            }

            return [
                'success' => false,
                'message' => 'All PIs rejected. Super Admin notified. User refund email sent.',
            ];
        }
        // Unique signed token তৈরি করো
        $token = Str::random(48) . '_' . time();

        // DB তে record রাখো
        $assignment = PiCaseAssignment::create([
            'complaint_id'     => $complaintId,
            'pi_id'            => $pi->id,
            'token'            => $token,
            'token_expires_at' => now()->addHours(self::TOKEN_HOURS),
            'status'           => 'pending',
            'mailed_at'        => now(),
        ]);

        // Complaint এ current PI info update করো
        $complaint->update([
            'status'              => 'PI Assignment Pending',
            'current_pi_email_id' => $pi->id,
            'pi_assignment_token' => $token,
            'pi_token_expires_at' => now()->addHours(self::TOKEN_HOURS),
        ]);

        // Email পাঠাও accept/reject বাটন সহ
        $emailResult = $this->sendPiAssignmentEmailWithActions($pi, $complaint, $token);

        return [
            'success'    => true,
            'message'    => "Case emailed to {$pi->full_name} ({$pi->pi_code})",
            'pi_code'    => $pi->pi_code,
            'email_sent' => $emailResult['success'],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // PUBLIC ROUTE: GET /pi/case/{token}/accept  OR  /reject
    // Email এর বাটনে ক্লিক করলে এই route এ আসবে
    // ─────────────────────────────────────────────────────────────

    public function handleEmailAction(Request $request, string $token, string $action)
    {
        // action শুধু 'accept' বা 'reject' হতে পারে
        if (!in_array($action, ['accept', 'reject'])) {
            return $this->htmlResponse('❌ Invalid Action', 'Invalid link.', 'error');
        }

        $assignment = PiCaseAssignment::where('token', $token)->first();

        // Token পাওয়া গেল না
        if (!$assignment) {
            return $this->htmlResponse('❌ Invalid Link', 'This link is invalid or has already been used.', 'error');
        }

        // Token expired
        if ($assignment->isExpired()) {
            return $this->htmlResponse(
                '⏰ Link Expired',
                'This link has expired. The case may have been reassigned.',
                'warning'
            );
        }

        // আগেই কোনো action নেওয়া হয়েছে
        if ($assignment->status !== 'pending') {
            $msg = $assignment->status === 'accepted'
                ? 'You already accepted this case.'
                : 'You already rejected this case.';
            return $this->htmlResponse('ℹ️ Already Responded', $msg, 'info');
        }

        $complaint = $assignment->complaint;
        $pi        = $assignment->pi;

        if (!$complaint || !$pi) {
            return $this->htmlResponse('❌ Error', 'Case or PI data not found.', 'error');
        }

        if ($action === 'accept') {
            return $this->handleAccept($assignment, $complaint, $pi, $request->ip());
        } else {
            return $this->handleReject($assignment, $complaint, $pi, $request->ip());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: Accept logic
    // ─────────────────────────────────────────────────────────────

    private function handleAccept(PiCaseAssignment $assignment, Complaint $complaint, PrivateInvestigator $pi, ?string $ip): \Illuminate\Http\Response
    {
        // Assignment record update
        $assignment->update([
            'status'    => 'accepted',
            'acted_at'  => now(),
            'action_ip' => $ip,
        ]);

        // Complaint update
        $complaint->update([
            'assigned_pi_id'      => $pi->id,
            'pi_assigned_at'      => now(),
            'status'              => 'Private Investigator Assigned',
            'pi_assignment_token' => null, // token clear করে দাও
        ]);

        // PI workload বাড়াও
        $pi->increment('active_cases');
        $pi->increment('total_cases');

        // User কে notification mail পাঠাও
        $this->sendUserAcceptedEmail($complaint, $pi);

        // Victim details বের করো
$victimInfo = '';
if (!$complaint->is_anonymous && $complaint->user_id) {
    $user = User::find($complaint->user_id);
    if ($user) {
        $victimInfo = "
            <div style='background:#0d1526;border:1px solid #1e3a6e;border-radius:10px;padding:16px 20px;margin-top:16px;text-align:left;'>
                <div style='font-size:11px;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:12px;'>👤 Victim Contact Details</div>
                <p style='color:#e2e8f0;font-size:14px;margin:6px 0;'>📛 Name: <strong style='color:#fff;'>{$user->name}</strong></p>
                <p style='color:#e2e8f0;font-size:14px;margin:6px 0;'>📧 Email: <strong style='color:#4f9eff;'>{$user->email}</strong></p>
                <p style='color:#e2e8f0;font-size:14px;margin:6px 0;'>📞 Phone: <strong style='color:#fff;'>{$user->phone}</strong></p>
            </div>";
    }
} else {
    $victimInfo = "
        <div style='background:#92400e20;border:1px solid #f59e0b40;border-radius:10px;padding:14px 18px;margin-top:16px;'>
            <p style='color:#fbbf24;font-size:13px;margin:0;'>⚠️ Anonymous case — the victim will contact you directly.</p>
        </div>";
}

return $this->htmlResponse(
    '✅ Case Accepted!',
    "You have accepted case <strong style='color:#4f9eff;'>{$complaint->complaint_id}</strong>.<br><br>
    The client has been notified. Please contact them within 48 hours.
    {$victimInfo}",
    'success',
    $pi->full_name,
    $complaint->complaint_id
);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: Reject logic
    // ─────────────────────────────────────────────────────────────

    private function handleReject(PiCaseAssignment $assignment, Complaint $complaint, PrivateInvestigator $pi, ?string $ip): \Illuminate\Http\Response
    {
        // Assignment record update
        $assignment->update([
            'status'    => 'rejected',
            'acted_at'  => now(),
            'action_ip' => $ip,
        ]);

        // পরের PI তে পাঠাও
        $result = $this->sendToNextPi($complaint->complaint_id);

        // ── In-app notification: user কে জানাও search চলছে ──
        if ($complaint->user_id && $result['success']) {
            UserNotification::notify(
                $complaint->user_id,
                'status_update',
                '🔄 Investigator Search Ongoing',
                "তোমার complaint {$complaint->complaint_id} এর জন্য আমরা আরেকজন Investigator খুঁজছি। শীঘ্রই update পাবে।",
                [
                    'complaint_id' => $complaint->complaint_id,
                    'action_url'   => '/track?id=' . $complaint->complaint_id,
                    'icon'         => '🔄',
                ]
            );
        }

        if ($result['success']) {
            $msg = "You rejected case {$complaint->complaint_id}. It has been forwarded to the next available investigator.";
        } else {
            $msg = "You rejected case {$complaint->complaint_id}. No other investigators are available right now. The Super Admin has been notified.";
        }

        return $this->htmlResponse(
            '❌ Case Rejected',
            $msg,
            'rejected',
            $pi->full_name,
            $complaint->complaint_id
        );
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL: PI কে case details + Accept/Reject বাটন সহ mail
    // ─────────────────────────────────────────────────────────────

    private function sendPiAssignmentEmailWithActions(PrivateInvestigator $pi, Complaint $complaint, string $token): array
    {
        $appUrl     = env('APP_URL', 'http://127.0.0.1:8000');
        $acceptUrl  = "{$appUrl}/api/pi/case/{$token}/accept";
        $rejectUrl  = "{$appUrl}/api/pi/case/{$token}/reject";
        $expiresIn  = self::TOKEN_HOURS . ' hours';

        $type      = ucfirst(str_replace('_', ' ', $complaint->type));
        $location  = $complaint->location ?: '—';
        $date      = $complaint->incident_date ? date('d M Y, H:i', strtotime($complaint->incident_date)) : '—';
        $submitted = date('d M Y, H:i', strtotime($complaint->submitted_at));
        $desc      = nl2br(htmlspecialchars($complaint->description ?? '—'));

        // Victim info row
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
                <tr><td style='padding:10px 0;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Email</td>
                        <td style='color:#4f9eff;font-size:14px;'>{$user->email}</td>
                    </tr></table></td></tr>";
            }
        } else {
            $victimRows = "
                <tr><td style='padding:10px 0;'>
                    <table width='100%'><tr>
                        <td style='color:#a0b4cc;font-size:13px;font-weight:600;width:140px;'>Victim Identity</td>
                        <td style='color:#fbbf24;font-size:14px;font-weight:700;'>⚠️ Anonymous — victim will contact you</td>
                    </tr></table></td></tr>";
        }

        // Evidence section
        $evidenceFiles   = ComplaintEvidence::where('complaint_id', $complaint->complaint_id)->get();
        $evidenceSection = '';
        if ($evidenceFiles->isNotEmpty()) {
            $fileRows = $evidenceFiles->map(function ($f) use ($appUrl) {
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

        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:600px;">

<!-- HEADER -->
<tr><td style="background:linear-gradient(135deg,#1a3a6e,#0d1f42);padding:28px 32px;border-radius:16px 16px 0 0;">
  <table width="100%"><tr>
    <td><div style="font-size:26px;margin-bottom:4px;">🛡️</div>
      <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">SafeVoice</h1>
      <p style="color:#a0b4cc;margin:4px 0 0;font-size:13px;">New Case Assignment Request</p>
    </td>
    <td style="text-align:right;vertical-align:top;">
      <div style="background:#a855f720;border:1px solid #a855f740;border-radius:10px;padding:10px 16px;display:inline-block;">
        <div style="color:#c084fc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Your PI Code</div>
        <div style="color:#fff;font-size:22px;font-weight:800;font-family:monospace;">{$pi->pi_code}</div>
      </div>
    </td>
  </tr></table>
</td></tr>

<!-- GREETING -->
<tr><td style="padding:24px 32px 0;">
  <p style="color:#a0b4cc;font-size:15px;margin:0;">Dear <strong style="color:#fff;">{$pi->full_name}</strong>,</p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:10px 0 0;">
    A new case has been matched to your profile. Please review the details below and
    <strong style="color:#4f9eff;">accept or reject within {$expiresIn}</strong>.
  </p>
</td></tr>

<!-- CASE ID BADGE -->
<tr><td style="padding:20px 32px;">
  <div style="background:#4f9eff10;border:1px solid #4f9eff40;border-radius:10px;padding:16px 20px;text-align:center;">
    <div style="font-size:11px;color:#a0b4cc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">Case ID</div>
    <div style="font-size:24px;font-weight:800;color:#4f9eff;font-family:monospace;">{$complaint->complaint_id}</div>
    <div style="font-size:12px;color:#a0b4cc;margin-top:4px;">Submitted: {$submitted}</div>
  </div>
</td></tr>

<!-- CASE DETAILS -->
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

<!-- DESCRIPTION -->
<tr><td style="padding:0 32px 20px;">
  <div style="background:#070d1a;border:1px solid #1e2d4a;border-radius:12px;padding:20px 24px;">
    <div style="font-size:12px;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:12px;">📝 Description</div>
    <p style="color:#a0b4cc;font-size:14px;line-height:1.8;margin:0;">{$desc}</p>
  </div>
</td></tr>

{$evidenceSection}

<!-- ✅ ACCEPT / ❌ REJECT BUTTONS — মেইলের সবচেয়ে গুরুত্বপূর্ণ অংশ -->
<tr><td style="padding:0 32px 28px;">
  <div style="background:#0d1f42;border:2px solid #1e3a6e;border-radius:14px;padding:24px;text-align:center;">
    <p style="color:#a0b4cc;font-size:13px;margin:0 0 20px;">
      ⏰ This link expires in <strong style="color:#fbbf24;">{$expiresIn}</strong>.
      Click once — the link becomes invalid after use.
    </p>

    <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
      <tr>
        <!-- ACCEPT BUTTON -->
        <td style="padding:0 8px;">
          <a href="{$acceptUrl}"
             style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);
                    color:#fff;font-size:16px;font-weight:700;text-decoration:none;
                    padding:16px 36px;border-radius:10px;letter-spacing:.3px;
                    border:none;box-shadow:0 4px 12px #16a34a40;">
            ✅ Accept Case
          </a>
        </td>
        <!-- REJECT BUTTON -->
        <td style="padding:0 8px;">
          <a href="{$rejectUrl}"
             style="display:inline-block;background:linear-gradient(135deg,#dc2626,#b91c1c);
                    color:#fff;font-size:16px;font-weight:700;text-decoration:none;
                    padding:16px 36px;border-radius:10px;letter-spacing:.3px;
                    border:none;box-shadow:0 4px 12px #dc262640;">
            ❌ Reject Case
          </a>
        </td>
      </tr>
    </table>

    <p style="color:#3a4a5e;font-size:11px;margin:16px 0 0;">
      If buttons don't work, copy these links:<br>
      Accept: <span style="color:#16a34a;">{$acceptUrl}</span><br>
      Reject: <span style="color:#dc2626;">{$rejectUrl}</span>
    </p>
  </div>
</td></tr>

<!-- WARNING -->
<tr><td style="padding:0 32px 24px;">
  <div style="background:#f59e0b10;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 18px;">
    <p style="color:#fbbf24;font-size:13px;margin:0;font-weight:600;">
      ⚠️ Do not share case details externally. Report updates to Super Admin only.
    </p>
  </div>
</td></tr>

<!-- FOOTER -->
<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice · Sent to {$pi->email}</p>
  <p style="color:#3a4a5e;font-size:11px;margin:6px 0 0;">Do not reply to this email. Contact your Super Admin for support.</p>
</td></tr>

</table></td></tr></table>
</body></html>
HTML;

        return $this->sendMail($pi->email, $pi->full_name, "SafeVoice — Case Assignment Request: {$complaint->complaint_id}", $html);
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL: User কে জানাও PI accept করেছে
    // ─────────────────────────────────────────────────────────────

    private function sendUserAcceptedEmail(Complaint $complaint, PrivateInvestigator $pi): void
    {
        if (!$complaint->user_id) return;
        $user = User::find($complaint->user_id);
        if (!$user || !$user->email) return;

        $type = ucfirst(str_replace('_', ' ', $complaint->type));

        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:560px;">

<tr><td style="background:linear-gradient(135deg,#1a3a6e,#0d1f42);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center;">
  <div style="font-size:28px;margin-bottom:6px;">🛡️</div>
  <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">SafeVoice</h1>
  <p style="color:#a0b4cc;margin:4px 0 0;font-size:13px;">Private Investigator Accepted Your Case</p>
</td></tr>

<tr><td style="padding:28px 32px 0;">
  <p style="color:#a0b4cc;font-size:15px;margin:0 0 10px;">
    Dear <strong style="color:#fff;">{$user->name}</strong>,
  </p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:0 0 20px;">
    Great news! A <strong style="color:#c084fc;">Private Investigator</strong> has
    <strong style="color:#2ecc71;">accepted your case</strong> and will contact you shortly via email.
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
  <div style="background:#2ecc7110;border-left:4px solid #2ecc71;border-radius:8px;padding:16px 18px;">
    <p style="color:#2ecc71;font-size:14px;margin:0;font-weight:600;">
      ✅ Your PI has accepted your case and will contact you shortly.
    </p>
    <p style="color:#a0b4cc;font-size:13px;margin:8px 0 0;">
      Please keep an eye on your email inbox. They will reach out within 48 hours.
    </p>
  </div>
</td></tr>

<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice · Protecting voices, securing futures.</p>
</td></tr>

</table></td></tr></table>
</body></html>
HTML;

        $this->sendMail($user->email, $user->name, "SafeVoice — Your PI has accepted case {$complaint->complaint_id}", $html);
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL: Super Admin কে notify করো — সব PI rejected
    // ─────────────────────────────────────────────────────────────


     private function sendUserRefundEmail(Complaint $complaint): void
    {
        if (!$complaint->user_id) return;
        $user = User::find($complaint->user_id);
        if (!$user || !$user->email) return;
 
        $type = ucfirst(str_replace('_', ' ', $complaint->type));
 
        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:560px;">
 
<tr><td style="background:linear-gradient(135deg,#7f1d1d,#450a0a);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center;">
  <div style="font-size:28px;margin-bottom:6px;">🛡️</div>
  <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">SafeVoice</h1>
  <p style="color:#fca5a5;margin:4px 0 0;font-size:13px;">Important Update on Your Case</p>
</td></tr>
 
<tr><td style="padding:28px 32px 0;">
  <p style="color:#a0b4cc;font-size:15px;margin:0 0 10px;">
    Dear <strong style="color:#fff;">{$user->name}</strong>,
  </p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:0 0 20px;">
    We regret to inform you that we were unable to assign a
    <strong style="color:#c084fc;">Private Investigator</strong> to your case at this time.
    All available investigators have declined the assignment.
  </p>
</td></tr>
 
<tr><td style="padding:0 32px 20px;">
  <div style="background:#7f1d1d20;border:1px solid #dc262640;border-radius:12px;padding:18px 22px;text-align:center;">
    <div style="font-size:11px;color:#fca5a5;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Case ID</div>
    <div style="font-size:22px;font-weight:800;color:#ef4444;font-family:monospace;">{$complaint->complaint_id}</div>
    <div style="font-size:13px;color:#a0b4cc;margin-top:6px;">Type: {$type}</div>
  </div>
</td></tr>
 
<tr><td style="padding:0 32px 20px;">
  <div style="background:#16a34a10;border-left:4px solid #16a34a;border-radius:8px;padding:16px 18px;">
    <p style="color:#4ade80;font-size:14px;margin:0;font-weight:600;">
      💰 Your payment will be refunded within 3–5 business days.
    </p>
    <p style="color:#a0b4cc;font-size:13px;margin:8px 0 0;">
      The refund will be processed to your original payment method automatically.
      If you have any questions, please contact our support team.
    </p>
  </div>
</td></tr>
 
<tr><td style="padding:0 32px 24px;">
  <div style="background:#1e3a6e20;border-left:4px solid #4f9eff;border-radius:8px;padding:14px 18px;">
    <p style="color:#a0b4cc;font-size:13px;margin:0;">
      ℹ️ Our Super Admin has been notified and will manually review your case.
      You may be contacted with further options.
    </p>
  </div>
</td></tr>
 
<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice · Protecting voices, securing futures.</p>
</td></tr>
 
</table></td></tr></table>
</body></html>
HTML;
 
        $this->sendMail(
            $user->email,
            $user->name,
            "SafeVoice — Update on Case {$complaint->complaint_id} & Refund Notice",
            $html
        );
    }

    private function notifySuperAdminAllRejected(Complaint $complaint): void
    {
        $superAdmin = SuperAdmin::first(); // বা where('is_primary', true)->first()
        if (!$superAdmin || !$superAdmin->email) return;

        $type         = ucfirst(str_replace('_', ' ', $complaint->type));
        $rejectedCount = PiCaseAssignment::where('complaint_id', $complaint->complaint_id)
            ->where('status', 'rejected')
            ->count();

        $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table width="560" cellpadding="0" cellspacing="0" style="background:#0d1526;border-radius:16px;border:2px solid #dc262640;max-width:560px;">

<tr><td style="background:linear-gradient(135deg,#7f1d1d,#450a0a);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center;">
  <div style="font-size:32px;margin-bottom:6px;">⚠️</div>
  <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">Action Required</h1>
  <p style="color:#fca5a5;margin:4px 0 0;font-size:13px;">All PIs Rejected This Case</p>
</td></tr>

<tr><td style="padding:28px 32px;">
  <p style="color:#fca5a5;font-size:15px;margin:0 0 16px;">
    Dear <strong style="color:#fff;">Super Admin</strong>,
  </p>
  <p style="color:#a0b4cc;font-size:14px;line-height:1.7;margin:0 0 20px;">
    All available Private Investigators have <strong style="color:#ef4444;">rejected</strong>
    the following case. <strong style="color:#fff;">Manual assignment is required.</strong>
  </p>

  <div style="background:#7f1d1d20;border:1px solid #dc262640;border-radius:12px;padding:18px 22px;text-align:center;margin-bottom:20px;">
    <div style="font-size:11px;color:#fca5a5;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Case ID</div>
    <div style="font-size:24px;font-weight:800;color:#ef4444;font-family:monospace;">{$complaint->complaint_id}</div>
    <div style="font-size:13px;color:#a0b4cc;margin-top:6px;">Type: {$type} · PIs tried: {$rejectedCount}</div>
  </div>

  <div style="background:#070d1a;border:1px solid #1e2d4a;border-radius:10px;padding:16px 20px;">
    <p style="color:#fbbf24;font-size:13px;margin:0;font-weight:600;">
      🔴 Please log in to the admin panel and manually assign a PI to this case immediately.
    </p>
  </div>
</td></tr>

<tr><td style="border-top:1px solid #1e2d4a;padding:20px 32px;text-align:center;">
  <p style="color:#3a4a5e;font-size:12px;margin:0;">© 2026 SafeVoice System Alert · {$complaint->complaint_id}</p>
</td></tr>

</table></td></tr></table>
</body></html>
HTML;

        $this->sendMail(
            $superAdmin->email,
            'Super Admin',
            "🔴 ACTION REQUIRED: All PIs Rejected Case {$complaint->complaint_id}",
            $html
        );

        // ── In-app notification: super admin dashboard এ দেখাবে ──
        SuperAdminNotification::notify(
            'all_pi_rejected',
            '🔴 All PIs Rejected — Manual Action Required',
            "Case {$complaint->complaint_id} ({$type}) এর জন্য সব {$rejectedCount} জন PI reject করেছে। Manual assignment দরকার।",
            [
                'complaint_id' => $complaint->complaint_id,
                'action_url'   => '/super-admin/dashboard',
                'icon'         => '⚠️',
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: PHPMailer দিয়ে mail পাঠাও
    // ─────────────────────────────────────────────────────────────

    private function sendMail(string $toEmail, string $toName, string $subject, string $html): array
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
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();

            return ['success' => true];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SafeVoice Mail Error [{$subject}]: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: Browser এ সুন্দর HTML response দেখাও
    // ─────────────────────────────────────────────────────────────

    private function htmlResponse(string $title, string $message, string $type, string $piName = '', string $caseId = ''): \Illuminate\Http\Response
    {
        $colors = [
            'success'  => ['bg' => '#16a34a', 'border' => '#15803d', 'icon' => '✅'],
            'rejected' => ['bg' => '#dc2626', 'border' => '#b91c1c', 'icon' => '❌'],
            'error'    => ['bg' => '#7f1d1d', 'border' => '#450a0a', 'icon' => '⛔'],
            'warning'  => ['bg' => '#92400e', 'border' => '#78350f', 'icon' => '⏰'],
            'info'     => ['bg' => '#1e3a6e', 'border' => '#1e2d4a', 'icon' => 'ℹ️'],
        ];
        $c = $colors[$type] ?? $colors['info'];

        $caseInfo = $caseId ? "<p style='color:#a0b4cc;font-size:13px;margin:10px 0 0;'>Case ID: <strong style='color:#4f9eff;'>{$caseId}</strong></p>" : '';
        $piInfo   = $piName ? "<p style='color:#a0b4cc;font-size:13px;margin:4px 0 0;'>PI: <strong style='color:#fff;'>{$piName}</strong></p>" : '';

        $html = <<<HTML
<!DOCTYPE html><html><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SafeVoice — {$title}</title>
</head>
<body style="margin:0;padding:0;background:#070d1a;font-family:'Segoe UI',Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;">
<div style="text-align:center;padding:40px 20px;">
  <div style="background:#0d1526;border:2px solid {$c['border']};border-radius:20px;padding:40px;max-width:480px;margin:0 auto;">
    <div style="font-size:56px;margin-bottom:16px;">{$c['icon']}</div>
    <h1 style="color:#fff;font-size:24px;margin:0 0 16px;font-weight:700;">{$title}</h1>
    <div style="background:{$c['bg']}20;border:1px solid {$c['border']}60;border-radius:10px;padding:16px 20px;margin-bottom:20px;">
      <p style="color:#e2e8f0;font-size:15px;margin:0;line-height:1.6;">{$message}</p>
    </div>
    {$caseInfo}
    {$piInfo}
    <p style="color:#3a4a5e;font-size:12px;margin:24px 0 0;">© 2026 SafeVoice · You can close this tab.</p>
  </div>
</div>
</body></html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}