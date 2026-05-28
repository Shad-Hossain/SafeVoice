<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EvidenceRequest;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\User;
use App\Models\UserNotification;
use App\Http\Controllers\FcmController;
use Carbon\Carbon;

class EvidenceRequestController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // POST /api/evidence-request/create
    // Admin → request more evidence from user for a complaint
    // ─────────────────────────────────────────────────────────
   public function create(Request $request)
{
    $request->validate([
        'complaint_id' => 'required|string',
        'admin_note'   => 'nullable|string|max:1000',
        'days'         => 'nullable|integer|in:7,30',
    ]);

    $days = $request->input('days', 7); // default 7, or 30 for fake/notice period

    $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
    if (!$complaint) {
        return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
    }

    $existing = EvidenceRequest::where('complaint_id', $request->complaint_id)
        ->whereIn('status', ['pending', 'skipped'])
        ->first();

    if ($existing) {
        // User exist করে কিনা check করো
        $checkId = $complaint->user_id ?? $complaint->anonymous_user_id;
        if (!$checkId || !User::where('id', $checkId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send evidence request — the user account no longer exists.',
            ], 422);
        }

        $existing->update([
            'admin_note'   => $request->input('admin_note', ''),
            'status'       => 'pending',
            'deadline'     => Carbon::now()->addDays($days),
            'days'         => $days,
            'skip_until'   => null,
            'responded_at' => null,
        ]);

        // 30-day notice refresh হলেও Probation নিশ্চিত করো
        if ($days === 30 && ($complaint->user_id || $complaint->anonymous_user_id)) {
            // anonymous হলেও anonymous_user_id দিয়ে Probation set করো
            $existingTargetId = $complaint->user_id ?? $complaint->anonymous_user_id;
            User::where('id', $existingTargetId)
                ->where('status', 'Active')
                ->update(['status' => 'Probation']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Evidence request refreshed. User will be notified.',
            'request_id' => $existing->id,
        ]);
    }

    // anonymous complaint হলে anonymous_user_id use করো
    $targetUserId = $complaint->user_id ?? $complaint->anonymous_user_id;

    // User exist করে কিনা check করো
    if (!$targetUserId || !User::where('id', $targetUserId)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot send evidence request — the user account no longer exists.',
        ], 422);
    }

    $evidenceRequest = EvidenceRequest::create([
        'complaint_id' => $request->complaint_id,
        'user_id'      => $targetUserId,
        'admin_note'   => $request->input('admin_note', ''),
        'status'       => 'pending',
        'deadline'     => Carbon::now()->addDays($days),
        'days'         => $days,
    ]);

    if ($targetUserId) {
        // ── 30-day notice → user কে Probation-এ দাও ──────────
        if ($days === 30) {
            User::where('id', $targetUserId)
                ->whereIn('status', ['Active'])
                ->update(['status' => 'Probation']);

            $deadline = Carbon::now()->addDays(30)->format('d M Y');

            // In-app notification
            UserNotification::notify(
                (int) $targetUserId,
                'probation_notice',
                '⚠️ Your Account is Under Review',
                "Your complaint {$request->complaint_id} has been flagged for review. Your account has been placed on probation. You have 30 days (until {$deadline}) to submit supporting evidence. During this period you cannot submit new complaints or use SOS.",
                [
                    'complaint_id' => $request->complaint_id,
                    'deadline'     => $deadline,
                    'icon'         => '⚠️',
                ]
            );

            $label = "⚠️ Your account is now on probation. You have 30 days to submit evidence for complaint {$request->complaint_id}. Deadline: {$deadline}.";
        } else {
            $label = "Admin has requested additional evidence for your complaint. You have 7 days to respond.";
        }

        FcmController::sendToUser(
            $targetUserId,
            '📋 Evidence Request — ' . $request->complaint_id,
            $label,
            ['type' => 'evidence_request', 'complaint_id' => $request->complaint_id, 'url' => '/dashboard']
        );
    }

    return response()->json([
        'success'    => true,
        'message'    => $days === 30
            ? 'User has been placed on probation and notified.'
            : 'Evidence request sent. User will be notified on next login.',
        'request_id' => $evidenceRequest->id,
    ]);
}
    // ─────────────────────────────────────────────────────────
    // GET /api/evidence-request/pending
    // User → get their pending evidence request (for notification modal)
    // ─────────────────────────────────────────────────────────
    public function getPending(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $now = Carbon::now();

        // Get all pending/skipped requests for this user
        $pendingRequests = EvidenceRequest::where('user_id', $userId)
            ->whereIn('status', ['pending', 'skipped'])
            ->where(function ($q) use ($now) {
                // Either not skipped, or skip period has expired
                $q->where('status', 'pending')
                  ->orWhere(function ($q2) use ($now) {
                      $q2->where('status', 'skipped')
                         ->where(function ($q3) use ($now) {
                             $q3->whereNull('skip_until')
                                ->orWhere('skip_until', '<=', $now);
                         });
                  });
            })
            ->with(['complaint:complaint_id,type,status'])
            ->get()
            ->map(function ($r) {
                return [
                    'id'           => $r->id,
                    'complaint_id' => $r->complaint_id,
                    'complaint_type' => $r->complaint?->type ?? '—',
                    'admin_note'   => $r->admin_note,
                    'deadline'     => $r->deadline,
                    'requested_at' => $r->requested_at,
                    'status'       => $r->status,
                ];
            });

        return response()->json([
            'success'  => true,
            'requests' => $pendingRequests,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/evidence-request/skip
    // User → skip for now (7 days snooze)
    // ─────────────────────────────────────────────────────────
    public function skip(Request $request)
    {
        $request->validate(['request_id' => 'required|integer']);

        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        $evReq = EvidenceRequest::where('id', $request->request_id)
            ->where('user_id', $userId)
            ->first();

        if (!$evReq) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        $evReq->update([
            'status'     => 'skipped',
            'skip_until' => Carbon::now()->addDays(7),
        ]);

        return response()->json(['success' => true, 'message' => 'Snoozed for 7 days.']);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/evidence-request/submit
    // User → submit evidence in response to request
    // (also uploads files via existing upload endpoint — this just marks as submitted)
    // ─────────────────────────────────────────────────────────
    public function markSubmitted(Request $request)
    {
        $request->validate(['request_id' => 'required|integer']);

        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        $evReq = EvidenceRequest::where('id', $request->request_id)
            ->where('user_id', $userId)
            ->first();

        if (!$evReq) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        $evReq->update([
            'status'       => 'submitted',
            'responded_at' => Carbon::now(),
        ]);

        // 30-day notice ছিল এবং evidence submit হলো → Probation থেকে Active করো
        if ($evReq->days >= 30 && $userId) {
            User::where('id', $userId)
                ->where('status', 'Probation')
                ->update(['status' => 'Active']);

            UserNotification::notify(
                (int) $userId,
                'probation_lifted',
                '✅ Probation Lifted — Account Restored',
                "Thank you for submitting your evidence for complaint {$evReq->complaint_id}. Your account has been restored to Active status. We will review your submission.",
                [
                    'complaint_id' => $evReq->complaint_id,
                    'icon'         => '✅',
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Evidence submitted successfully.']);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/evidence-request/admin-list?complaint_id=SV-xxx
    // Admin → see all requests for a complaint
    // ─────────────────────────────────────────────────────────
    public function adminList(Request $request)
    {
        $requests = EvidenceRequest::where('complaint_id', $request->complaint_id)
            ->orderByDesc('requested_at')
            ->get();

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/evidence-request/check-expired
    // Cron / page load এ চলে → expired case গুলো auto-process করে
    //
    // কী করে:
    //  1. Evidence deadline পেরিয়ে গেছে → status = 'expired'
    //  2. সেই complaint এর status → 'PI Notification Sent' (PI কে notify করো)
    //  3. Admin dashboard এ notification
    //  4. ✅ Automatic: PI কে mail পাঠাও (triggerPIFromExpired এর কাজ automatic)
    // ─────────────────────────────────────────────────────────
    public function checkExpired()
    {
        $now = Carbon::now();

        // Step 1: যেসব evidence request এর deadline পেরিয়ে গেছে
        $expired = EvidenceRequest::whereIn('status', ['pending', 'skipped'])
            ->where('deadline', '<=', $now)
            ->get();

        $autoProcessed = [];
        $alreadyDone   = [];

        foreach ($expired as $r) {
            // Expired mark করো
            $r->update(['status' => 'expired']);

            $complaint = Complaint::where('complaint_id', $r->complaint_id)->first();
            if (!$complaint) continue;

            // ✅ Automatic PI notification:
            // Complaint status → 'PI Notification Sent' মানে system PI কে mail করবে
            // শুধু তখনই করো যখন status এখনো পুরনো stage এ আছে
            $eligibleStatuses = ['Under Review', 'Submitted', 'PI Payment Pending'];

            if (in_array($complaint->status, $eligibleStatuses)) {
                // ── Complaint status আপডেট ──────────────────────────
                $complaint->update(['status' => 'PI Notification Sent']);

                // ── PI কে automatic mail করো ─────────────────────────
                $assignmentController = new \App\Http\Controllers\PiCaseAssignmentController();
                $result = $assignmentController->sendToNextPi($complaint->complaint_id);

                // ── User কে notification দাও (anonymous হলেও) ────────
                $notifyUserId = $complaint->user_id
                    ?? \App\Models\ComplaintPrincipal::getUserId($complaint->complaint_id);

                if ($notifyUserId) {
                    \App\Models\UserNotification::notify(
                        $notifyUserId,
                        'evidence_expired_pi_notified',
                        '⚠️ Evidence Deadline Passed',
                        "তোমার complaint {$complaint->complaint_id} এর evidence deadline পেরিয়ে গেছে। "
                            . "আমরা তোমার পক্ষে একজন Private Investigator কে notify করেছি।",
                        [
                            'complaint_id' => $complaint->complaint_id,
                            'action_url'   => '/track?id=' . $complaint->complaint_id,
                            'icon'         => '⚠️',
                        ]
                    );
                }

                $autoProcessed[] = [
                    'complaint_id' => $complaint->complaint_id,
                    'pi_notified'  => $result['success'] ?? false,
                    'pi_message'   => $result['message'] ?? '',
                ];
            } else {
                $alreadyDone[] = $complaint->complaint_id;
            }
        }

        // ── Admin dashboard notification ──────────────────────────────
        if (count($autoProcessed) > 0) {
            $cases = implode(', ', array_column($autoProcessed, 'complaint_id'));

            // Super admin notification
            try {
                \App\Models\SuperAdminNotification::create([
                    'type'    => 'evidence_expired_auto',
                    'title'   => '⚠️ Evidence Deadline Auto-Processed',
                    'message' => count($autoProcessed) . " complaint(s) had evidence deadline pass. "
                        . "PI automatically notified for: {$cases}",
                    'data'    => json_encode(['complaint_ids' => array_column($autoProcessed, 'complaint_id')]),
                    'is_read' => false,
                ]);
            } catch (\Exception $e) {
                // SuperAdminNotification নাও থাকতে পারে — silent fail
            }

            // FCM push to admin
            $adminUsers = \App\Models\User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                FcmController::sendToUser(
                    $admin->id,
                    '⚠️ Evidence Deadline — PI Auto-Notified',
                    count($autoProcessed) . " case(s) auto-processed: {$cases}",
                    ['type' => 'evidence_expired_auto', 'url' => '/admin/dashboard#']
                );
            }
        }

        return response()->json([
            'success'         => true,
            'expired_count'   => $expired->count(),
            'auto_processed'  => $autoProcessed,
            'already_done'    => $alreadyDone,
            'expired_cases'   => $expired->pluck('complaint_id'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/evidence-request/expired-list
    // Admin → see all expired (failed to submit) evidence requests
    // ─────────────────────────────────────────────────────────
    public function expiredList()
    {
        $expired = EvidenceRequest::where('status', 'expired')
            ->orderByDesc('requested_at')
            ->get();

        return response()->json(['success' => true, 'expired' => $expired]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/evidence-request/reject
    // User → reject/dismiss an evidence request
    // ─────────────────────────────────────────────────────────
    public function reject(Request $request)
    {
        $request->validate(['request_id' => 'required|integer']);

        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        $evReq = EvidenceRequest::where('id', $request->request_id)
            ->where('user_id', $userId)
            ->first();

        if (!$evReq) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }

        $evReq->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Request dismissed.']);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/evidence-request/expired
    // User/Admin → list expired evidence requests
    // ─────────────────────────────────────────────────────────
    public function getExpired(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id');

        $query = EvidenceRequest::where('status', 'expired');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return response()->json([
            'success'  => true,
            'expired'  => $query->orderByDesc('requested_at')->get(),
        ]);
    }
}