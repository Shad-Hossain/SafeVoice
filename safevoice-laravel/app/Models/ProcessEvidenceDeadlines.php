<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EvidenceRequest;
use App\Models\UserNotification;
use App\Models\User;
use Carbon\Carbon;

class ProcessEvidenceDeadlines extends Command
{
    protected $signature   = 'safevoice:process-evidence-deadlines';
    protected $description = 'Process expired evidence request deadlines — auto suspend (30-day) and PI notify (7-day)';

    public function handle(): void
    {
        $now = Carbon::now();

        // ── expired pending evidence requests ──────────────────
        $expired = EvidenceRequest::where('status', 'pending')
            ->where('deadline', '<', $now)
            ->with(['complaint', 'user'])
            ->get();

        foreach ($expired as $req) {
            // ── 30-day fake complaint notice expired → auto suspend ──
            if ($req->days >= 30) {
                $this->handle30DayExpiry($req);
            }

            // ── 7-day normal request expired → PI notification ──
            if ($req->days <= 7) {
                $this->handle7DayExpiry($req);
            }

            // deadline processed — status update করো
            $req->update(['status' => 'expired']);
        }

        // ── 2 months পরে auto-reactivate suspended users ──────
        $this->reactivateSuspendedUsers($now);

        $this->info('Evidence deadline processing complete.');
    }

    // ── 30-day expired: user auto-suspend ─────────────────────
    private function handle30DayExpiry(EvidenceRequest $req): void
    {
        $userId = $req->user_id
            ?? optional($req->complaint)->user_id
            ?? optional($req->complaint)->anonymous_user_id;

        if (!$userId) return;

        $user = User::where('id', $userId)
            ->whereNotIn('status', ['Banned'])
            ->first();

        if (!$user) return;

        $suspensionCount  = ($user->suspension_count ?? 0) + 1;
        $activationDate   = Carbon::now()->addMonths(2)->format('d M Y');
        $remainingStrikes = 3 - $suspensionCount;

        if ($suspensionCount >= 3) {
            $user->update([
                'status'           => 'Banned',
                'suspension_count' => $suspensionCount,
            ]);
            // Anonymous user হলে email পাঠাবো না — identity expose হবে
            if ($req->complaint && $req->complaint->user_id) {
                $this->sendSuspensionEmail($user, $suspensionCount, null, true);
            }
            $this->info("User #{$userId} auto-banned after 3 suspensions.");
        } else {
            $user->update([
                'status'           => 'Suspended',
                'suspension_count' => $suspensionCount,
                'suspended_until'  => Carbon::now()->addMonths(2),
            ]);

            // Anonymous user হলে email পাঠাবো না — login screen-এ message দেখাবে
            if ($req->complaint && $req->complaint->user_id) {
                $this->sendSuspensionEmail($user, $suspensionCount, $activationDate, false, $remainingStrikes);
            }

            UserNotification::notify(
                (int) $userId,
                'account_suspended',
                '⚠️ Your Account Has Been Suspended',
                "Your complaint {$req->complaint_id} was flagged and you did not submit evidence within the required 30-day period. Your account has been suspended for 2 months ({$suspensionCount}/3 strikes). Activation date: {$activationDate}.",
                [
                    'complaint_id' => $req->complaint_id,
                    'icon'         => '⚠️',
                    'strikes'      => $suspensionCount,
                ]
            );

            $this->info("User #{$userId} suspended ({$suspensionCount}/3) for complaint {$req->complaint_id}.");
        }
    }

    // ── Suspension email via PHPMailer ─────────────────────────
    private function sendSuspensionEmail(User $user, int $count, ?string $activationDate, bool $isBanned, int $remaining = 0): void
    {
        if (!$user->email) return;

        try {
            $mailerPath = base_path('PHPMailer-master/src');
            require_once $mailerPath . '/Exception.php';
            require_once $mailerPath . '/PHPMailer.php';
            require_once $mailerPath . '/SMTP.php';

            $mail             = new \PHPMailer\PHPMailer\PHPMailer(true);
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
            $mail->Subject = $isBanned
                ? 'SafeVoice — Your Account Has Been Permanently Banned'
                : "SafeVoice — Account Suspended (Strike {$count}/3)";
            $mail->Body    = $this->suspensionEmailTemplate($user, $count, $activationDate, $isBanned, $remaining);
            $mail->AltBody = $this->suspensionEmailPlainText($user, $count, $activationDate, $isBanned, $remaining);
            $mail->send();

            $this->info("Suspension email sent to {$user->email}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Suspension email failed: ' . $e->getMessage());
        }
    }

    private function suspensionEmailTemplate(User $user, int $count, ?string $activationDate, bool $isBanned, int $remaining): string
    {
        $name     = htmlspecialchars($user->name);
        $ordinal  = ['', '1st', '2nd', '3rd'][$count] ?? "{$count}th";

        if ($isBanned) {
            $statusBlock = "
                <div style='background:#2d0a0a;border:1px solid #e6394640;border-radius:12px;padding:20px 24px;margin:24px 0;text-align:center;'>
                    <p style='margin:0 0 8px;font-size:28px;'>🚫</p>
                    <p style='margin:0;color:#e63946;font-size:18px;font-weight:700;'>Permanently Banned</p>
                    <p style='margin:8px 0 0;color:#a0b4cc;font-size:13px;'>Your account has been permanently removed from SafeVoice.</p>
                </div>";
            $messageBlock = "
                <p style='color:#cbd5e1;font-size:14px;line-height:1.7;'>
                    After <strong style='color:#e63946;'>3 suspensions</strong> due to submitting fake or unsubstantiated complaints,
                    your account has been <strong style='color:#e63946;'>permanently banned</strong> from SafeVoice.
                    This action cannot be reversed.
                </p>";
        } else {
            $statusBlock = "
                <div style='background:#1a120a;border:1px solid #f39c1240;border-radius:12px;padding:20px 24px;margin:24px 0;'>
                    <table width='100%'>
                        <tr>
                            <td style='color:#a0b4cc;font-size:13px;font-weight:600;padding:8px 0;width:160px;'>Suspension</td>
                            <td style='color:#f39c12;font-size:14px;font-weight:700;'>{$ordinal} suspension &nbsp;<span style='color:#4a5568;font-weight:400;'>({$count} of 3)</span></td>
                        </tr>
                        <tr>
                            <td style='color:#a0b4cc;font-size:13px;font-weight:600;padding:8px 0;'>Duration</td>
                            <td style='color:#fff;font-size:14px;'>60 days</td>
                        </tr>
                        <tr>
                            <td style='color:#a0b4cc;font-size:13px;font-weight:600;padding:8px 0;'>Activation Date</td>
                            <td style='color:#2ecc71;font-size:14px;font-weight:700;'>{$activationDate}</td>
                        </tr>
                        <tr>
                            <td style='color:#a0b4cc;font-size:13px;font-weight:600;padding:8px 0;'>Remaining Strikes</td>
                            <td style='color:#e63946;font-size:14px;font-weight:700;'>{$remaining} more suspension" . ($remaining === 1 ? '' : 's') . " and your account will be <strong>permanently banned</strong></td>
                        </tr>
                    </table>
                </div>";
            $messageBlock = "
                <p style='color:#cbd5e1;font-size:14px;line-height:1.7;'>
                    Your account has been suspended because you submitted a complaint that was flagged as fake
                    and you did not provide supporting evidence within the required 30-day period.
                    Your account will be <strong style='color:#2ecc71;'>automatically reactivated on {$activationDate}</strong>.
                </p>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <body style='margin:0;padding:0;background:#060c18;font-family:Arial,sans-serif;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#060c18;padding:40px 20px;'>
            <tr><td align='center'>
            <table width='600' cellpadding='0' cellspacing='0' style='background:#0a0f1e;border:1px solid #1e2d4a;border-radius:16px;overflow:hidden;max-width:600px;'>
                <!-- Header -->
                <tr>
                    <td style='background:linear-gradient(135deg,#1a0a0a,#2d1515);padding:32px 40px;text-align:center;'>
                        <p style='margin:0 0 8px;font-size:36px;'>⚠️</p>
                        <h1 style='margin:0;color:#fff;font-size:22px;font-weight:700;'>
                            " . ($isBanned ? 'Account Permanently Banned' : 'Account Suspended') . "
                        </h1>
                        <p style='margin:8px 0 0;color:#a0b4cc;font-size:13px;'>SafeVoice Enforcement Notice</p>
                    </td>
                </tr>
                <!-- Body -->
                <tr>
                    <td style='padding:32px 40px;'>
                        <p style='color:#cbd5e1;font-size:15px;margin:0 0 16px;'>Dear <strong style='color:#fff;'>{$name}</strong>,</p>
                        {$messageBlock}
                        {$statusBlock}
                        <p style='color:#6b7280;font-size:12px;margin:24px 0 0;line-height:1.6;'>
                            If you believe this action was taken in error, please contact SafeVoice support.<br>
                            This is an automated message from the SafeVoice enforcement system.
                        </p>
                    </td>
                </tr>
                <!-- Footer -->
                <tr>
                    <td style='background:#060c18;padding:20px 40px;text-align:center;border-top:1px solid #1e2d4a;'>
                        <p style='margin:0;color:#4a5568;font-size:12px;'>© SafeVoice — Protecting truth, one complaint at a time.</p>
                    </td>
                </tr>
            </table>
            </td></tr>
        </table>
        </body>
        </html>";
    }

    private function suspensionEmailPlainText(User $user, int $count, ?string $activationDate, bool $isBanned, int $remaining): string
    {
        $ordinal = ['', '1st', '2nd', '3rd'][$count] ?? "{$count}th";
        if ($isBanned) {
            return "Dear {$user->name},\n\nYour account has been permanently banned from SafeVoice after 3 suspensions for submitting fake complaints.\n\nThis action cannot be reversed.\n\n— SafeVoice System";
        }
        return "Dear {$user->name},\n\nYour SafeVoice account has been suspended.\n\nThis is your {$ordinal} suspension ({$count}/3).\nYour account will be automatically activated on: {$activationDate}.\n{$remaining} more suspension" . ($remaining === 1 ? '' : 's') . " and your account will be permanently banned.\n\n— SafeVoice System";
    }

    // ── 7-day expired: PI notification send করো ───────────────
    private function handle7DayExpiry(EvidenceRequest $req): void
    {
        $userId = $req->user_id
            ?? optional($req->complaint)->user_id
            ?? optional($req->complaint)->anonymous_user_id;

        if (!$userId) return;

        // Already notified check করো
        $alreadyNotified = UserNotification::where('user_id', $userId)
            ->where('type', 'evidence_deadline_missed')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.complaint_id')) = ?", [$req->complaint_id])
            ->exists();

        if ($alreadyNotified) return;

        UserNotification::notify(
            (int) $userId,
            'evidence_deadline_missed',
            '📋 Evidence Submission Deadline Passed',
            "You did not submit evidence for complaint {$req->complaint_id} within 7 days. The assigned PI (Police Inspector) has been notified and will follow up with you directly.",
            [
                'complaint_id' => $req->complaint_id,
                'icon'         => '📋',
            ]
        );

        $this->info("PI notification sent for user #{$userId}, complaint {$req->complaint_id}.");
    }

    // ── 2 months পরে suspended users auto-reactivate ──────────
    private function reactivateSuspendedUsers(Carbon $now): void
    {
        $users = User::where('status', 'Suspended')
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', $now)
            ->get();

        foreach ($users as $user) {
            $user->update([
                'status'          => 'Active',
                'suspended_until' => null,
            ]);

            UserNotification::notify(
                (int) $user->id,
                'account_reactivated',
                '✅ Your Account Has Been Reactivated',
                'Your 2-month suspension period has ended. Your account is now active again. Please ensure all future complaints are genuine.',
                ['icon' => '✅']
            );

            $this->info("User #{$user->id} auto-reactivated after suspension period.");
        }
    }
}