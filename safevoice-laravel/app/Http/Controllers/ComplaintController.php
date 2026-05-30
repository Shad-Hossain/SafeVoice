<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\ComplaintPrincipal;
use App\Models\Officer;
use App\Models\User;
use App\Http\Controllers\FcmController;

class ComplaintController extends Controller
{
    /**
     * ✅ Helper: authenticated user এর ID বের করো।
     * NEVER client থেকে user_id নেওয়া হবে না।
     */
    private function getAuthUserId(Request $request): ?int
    {
        // 1. Sanctum Bearer token
        try {
            if ($user = $request->user()) {
                return $user->id;
            }
        } catch (\Exception $e) {}

        // 2. Session (cookie-based login)
        try {
            if ($id = $request->session()->get('user_id')) {
                return (int) $id;
            }
        } catch (\Exception $e) {}

        // 3. JSON body (frontend payload এ user_id পাঠায়)
        if ($id = $request->input('user_id')) {
            // DB তে user exist করে কিনা verify করো
            if (\App\Models\User::where('id', (int)$id)->exists()) {
                return (int) $id;
            }
        }

        // 4. Query param fallback
        if ($id = $request->query('user_id')) {
            if (\App\Models\User::where('id', (int)$id)->exists()) {
                return (int) $id;
            }
        }

        return null;
    }

    // GET /api/complaints (Admin only — sensitive data)
    public function index(Request $request)
    {
        $query = Complaint::query()
            ->select(
                'id', 'complaint_id', 'type', 'incident_date', 'location',
                'description', 'is_anonymous', 'status', 'submitted_at', 'updated_at',
                'assigned_pi_id', 'pi_assigned_at', 'legal_consent', 'publish_consent', 'admin_message'
                // ✅ user_id, anonymous_user_id admin list এ নেই — কে করেছে admin দেখবে না
            );

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('type'))   $query->where('type',   $request->type);

        $complaints = $query->orderByDesc('submitted_at')->get();

        $stats = Complaint::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return response()->json([
            'success'    => true,
            'complaints' => $complaints,
            'total'      => $complaints->count(),
            'stats'      => $stats,
        ]);
    }

    // GET /api/complaints/{id} (Admin only)
    public function show($id)
    {
        $complaint = Complaint::where('complaint_id', $id)
            ->select(
                'id', 'complaint_id', 'type', 'incident_date', 'location',
                'description', 'is_anonymous', 'status', 'submitted_at',
                'updated_at', 'assigned_pi_id', 'pi_assigned_at', 'evidence_files',
                'legal_consent', 'publish_consent'
                // ✅ user_id এবং anonymous_user_id এখানে নেই
            )
            ->first();

        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
        }

        // ✅ শুধু non-anonymous complaint এর submitter দেখাও
        $submittedBy = null;
        if (!$complaint->is_anonymous) {
            // user_id select ছাড়া Complaint এ নেই, তাই আলাদা query
            $fullComplaint = Complaint::where('complaint_id', $id)->select('user_id')->first();
            if ($fullComplaint && $fullComplaint->user_id) {
                $user = User::where('id', $fullComplaint->user_id)
                    ->select('id', 'name', 'email', 'phone', 'status')
                    ->first();
                if ($user) {
                    $submittedBy = [
                        'user_id' => $user->id,
                        'name'    => $user->name,
                        'email'   => $user->email,
                        'phone'   => $user->phone,
                        'status'  => $user->status,
                    ];
                }
            }
        }
        // ✅ anonymous complaint হলে submittedBy = null — admin জানবে না কে করেছে

        return response()->json([
            'success'      => true,
            'complaint'    => $complaint,
            'submitted_by' => $submittedBy,
        ]);
    }

    // POST /api/complaints/submit
    public function submit(Request $request)
    {
        // ✅ user_id শুধু token/session থেকে — client input থেকে কখনো নয়
        $userId = $this->getAuthUserId($request);

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }

        try {
        // ── Main submit logic ──
        $user = User::find($userId);
        if ($user && $user->status === 'Probation') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently on probation. You cannot submit new complaints.',
                'status'  => 'Probation',
            ], 403);
        }
        if ($user && in_array($user->status, ['Suspended', 'Banned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended. You cannot submit new complaints.',
                'status'  => $user->status,
            ], 403);
        }

        $request->validate([
            'type'        => 'required|string',
            'description' => 'required|string',
        ]);

        $complaintId = 'SV-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $incidentDate = null;
        if ($request->filled('incident_date')) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $request->incident_date);
            if ($dt) $incidentDate = $dt->format('Y-m-d H:i:s');
        }

        $isAnonymous    = $request->boolean('is_anonymous');
        $legalConsent   = $request->input('legal_consent');
        $publishConsent = $request->input('publish_consent');
        $status = ($legalConsent === 'no' && $publishConsent === 'no') ? 'Rejected' : 'Submitted';

        $complaint = Complaint::create([
            'complaint_id'  => $complaintId,

            // ✅ Non-anonymous: user_id রাখো (admin দেখবে)
            // ✅ Anonymous: user_id null (admin কখনো জানবে না কে করেছে)
            'user_id'       => $isAnonymous ? null : $userId,

            // ✅ HMAC hash — plaintext user_id নয়
            // DB চুরি হলেও কেউ বলতে পারবে না কোন hash কোন user এর
            // User নিজে verify করতে পারবে কারণ same input → same hash
            // HMAC hash — plaintext user_id নয়, DB চুরি হলেও reverse করা যাবে না
            'anonymous_user_id' => hash_hmac('sha256', (string) $userId, config('app.key')),

            'type'          => $request->type,
            'incident_date' => $incidentDate,
            'location'      => $request->location ?? '',
            'description'   => $request->description,
            'is_anonymous'  => $isAnonymous,
            'status'        => $status,
            'legal_consent' => $legalConsent,
            'publish_consent' => $publishConsent,
        ]);

        // ✅ complaint_principals table এ encrypted user_id রাখো
        // try-catch: table না থাকলেও complaint submit হবে
        try {
            ComplaintPrincipal::store($complaintId, $userId);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ComplaintPrincipal store failed: ' . $e->getMessage());
        }

        if (!$isAnonymous) {
            try { User::where('id', $userId)->increment('complaints_count'); } catch (\Exception $e) {}
        }

        try {
            \App\Models\UserNotification::notify(
                $userId, 'complaint_submitted', 'Complaint Submitted',
                "Your complaint {$complaint->complaint_id} was submitted successfully.",
                ['complaint_id' => $complaint->complaint_id, 'action_url' => '/track?id=' . $complaint->complaint_id]
            );
        } catch (\Exception $e) {}

        return response()->json([
            'success'      => true,
            'complaint_id' => $complaintId,
            'message'      => 'Complaint submitted successfully',
        ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Complaint submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // POST /api/complaints/update-status (Admin only)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'complaint_id' => 'required|string',
            'status'       => 'required|in:Submitted,Under Review,PI Notification Sent,PI Payment Confirmed,Private Investigator Assigned,Resolved,Rejected',
        ]);

        $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
        }

        if ($request->status === 'Private Investigator Assigned') {
            $officer = Officer::where('is_active', true)->orderBy('assigned_cases')->first();
            if (!$officer) {
                return response()->json(['success' => false, 'message' => 'No active officers available'], 503);
            }
            $complaint->update([
                'status'                => $request->status,
                'assigned_officer_code' => $officer->officer_code,
            ]);
            $officer->increment('assigned_cases');
            $this->notifyUser($complaint, 'Private Investigator Assigned');
            return response()->json(['success' => true, 'message' => 'Status updated.']);
        }

        $oldStatus = $complaint->status;
        $complaint->update([
            'status'        => $request->status,
            'admin_message' => $request->input('admin_message', ''),
        ]);

        if (in_array($request->status, ['Resolved', 'Rejected'])
            && !in_array($oldStatus, ['Resolved', 'Rejected'])
            && $complaint->assigned_pi_id)
        {
            \App\Models\PrivateInvestigator::where('id', $complaint->assigned_pi_id)
                ->where('active_cases', '>', 0)
                ->decrement('active_cases');

            $pendingCases = \App\Models\Complaint::where('status', 'PI Payment Pending Confirmation')
                ->orderBy('submitted_at')
                ->get();

            if ($pendingCases->isNotEmpty()) {
                $assignmentController = new \App\Http\Controllers\PiCaseAssignmentController();
                foreach ($pendingCases as $pending) {
                    $hasCapacity = \App\Models\PrivateInvestigator::where('is_active', true)
                        ->where('active_cases', '<', 10)
                        ->exists();
                    if (!$hasCapacity) break;
                    $result = $assignmentController->sendToNextPi($pending->complaint_id);
                    if ($result['success']) {
                        $this->notifyUser($pending, 'pi_search_started');
                    }
                }
            }
        }

        $this->notifyUser($complaint, $request->status);
        return response()->json(['success' => true, 'message' => 'Status updated to ' . $request->status]);
    }

    /**
     * User কে notification পাঠাও
     * ✅ complaint_principals থেকে encrypted user_id decrypt করে নাও
     * Admin এর কাছে এই mapping নেই
     */
    private function notifyUser(Complaint $complaint, string $status): void
    {
        // ✅ non-anonymous: user_id সরাসরি আছে
        // ✅ anonymous: complaint_principals থেকে decrypt করো
        $notifyUserId = $complaint->user_id
            ?? ComplaintPrincipal::getUserId($complaint->complaint_id);

        if (!$notifyUserId) return;

        $msgs = [
            'Under Review'                  => ['icon' => '🔍', 'title' => '🔍 Complaint Under Review',           'msg' => "তোমার complaint {$complaint->complaint_id} এখন review এ আছে।"],
            'PI Notification Sent'          => ['icon' => '🕵️', 'title' => '🕵️ Private Investigator Notification', 'msg' => "তোমার complaint {$complaint->complaint_id} এর জন্য PI review শুরু হয়েছে।"],
            'PI Payment Confirmed'          => ['icon' => '💳', 'title' => '💳 Payment Confirmed',                 'msg' => "Payment confirmed। শীঘ্রই PI assign হবে।"],
            'Private Investigator Assigned' => ['icon' => '✅', 'title' => '✅ PI Assigned',                        'msg' => "তোমার complaint {$complaint->complaint_id} এ একজন PI assign হয়েছেন।"],
            'Resolved'                      => ['icon' => '🎉', 'title' => '🎉 Complaint Resolved',                 'msg' => "তোমার complaint {$complaint->complaint_id} resolve হয়েছে। ধন্যবাদ!"],
            'Rejected'                      => ['icon' => '❌', 'title' => '❌ Complaint Rejected',                 'msg' => "তোমার complaint {$complaint->complaint_id} process করা সম্ভব হয়নি।"],
            'pi_search_started'             => ['icon' => '🔄', 'title' => '🔄 Investigator Search Started',       'msg' => "তোমার complaint {$complaint->complaint_id} এর জন্য Investigator খোঁজা শুরু হয়েছে।"],
        ];

        $info = $msgs[$status] ?? null;
        if ($info) {
            \App\Models\UserNotification::notify(
                $notifyUserId,
                'status_update',
                $info['title'],
                $info['msg'],
                ['complaint_id' => $complaint->complaint_id, 'action_url' => '/track?id=' . $complaint->complaint_id, 'icon' => $info['icon']]
            );
        }

        // FCM Push — শুধু non-anonymous এর জন্য (anonymous user কে FCM push দিলে privacy leak)
        if ($complaint->user_id) {
            $pushMsgs = [
                'Under Review'                 => ['icon' => '🔍', 'msg' => 'Your complaint is now under review.'],
                'PI Notification Sent'         => ['icon' => '🕵️', 'msg' => 'A PI review has been initiated.'],
                'PI Payment Confirmed'         => ['icon' => '💳', 'msg' => 'Payment confirmed. PI will be assigned.'],
                'Private Investigator Assigned'=> ['icon' => '✅', 'msg' => 'A PI has been assigned to your complaint.'],
                'Resolved'                     => ['icon' => '🎉', 'msg' => 'Your complaint has been resolved.'],
                'Rejected'                     => ['icon' => '❌', 'msg' => 'Your complaint could not be processed.'],
            ];
            $pinfo = $pushMsgs[$status] ?? ['icon' => '🔔', 'msg' => 'Complaint status updated.'];
            FcmController::sendToUser(
                $complaint->user_id,
                $pinfo['icon'] . ' Complaint ' . $complaint->complaint_id . ' — ' . $status,
                $pinfo['msg'],
                ['type' => 'status_update', 'complaint_id' => $complaint->complaint_id, 'status' => $status]
            );
        }
    }

    // GET /api/my-complaints
    public function myComplaints(Request $request)
    {
        // ✅ user_id শুধু token থেকে
        $userId = $this->getAuthUserId($request);

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ নিজের HMAC hash বানাও এবং anonymous_user_id এর সাথে compare করো
        $myHash = hash_hmac('sha256', (string) $userId, config('app.key'));

        $complaints = Complaint::where(function ($q) use ($userId, $myHash) {
                $q->where('user_id', $userId)          // normal complaints
                  ->orWhere('anonymous_user_id', $myHash); // anonymous complaints (hash match)
            })
            ->orderByDesc('submitted_at')
            ->get();

        return response()->json(['success' => true, 'complaints' => $complaints]);
    }

    // GET /api/track_complaint?id=SV-2026-XXXX (Public — শুধু status দেখাবে)
    public function track(Request $request)
    {
        $id = strtoupper(trim($request->query('id', '')));
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Complaint ID required.'], 400);
        }

        $complaint = Complaint::where('complaint_id', $id)->first();
        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'No complaint found.'], 404);
        }

        // ✅ track endpoint এ শুধু safe fields — user_id বা anonymous_user_id নেই
        return response()->json([
            'success'   => true,
            'complaint' => [
                'complaint_id'  => $complaint->complaint_id,
                'type'          => $complaint->type,
                'location'      => $complaint->location,
                'status'        => $complaint->status,
                'is_anonymous'  => $complaint->is_anonymous,
                'submitted_at'  => $complaint->submitted_at,
                'incident_date' => $complaint->incident_date,
            ],
        ]);
    }
    // GET /api/pi/anonymous-contact
    // Anonymous complaint এ PI assign হলে victim এর জন্য PI contact info দাও
    // শুধু তখনই দেবে যখন: is_anonymous=true, assigned_pi_id not null, pi_contact_acknowledged=false
    public function anonymousPIContact(Request $request)
    {
        $userId = $this->getAuthUserId($request);
        if (!$userId) {
            return response()->json(['success' => true, 'pending' => []]);
        }

        $myHash = hash_hmac('sha256', (string) $userId, config('app.key'));

        // Anonymous complaint গুলো যেখানে PI assign হয়েছে কিন্তু victim এখনো acknowledge করেনি
        $complaints = Complaint::where('is_anonymous', true)
            ->where('anonymous_user_id', $myHash)
            ->whereNotNull('assigned_pi_id')
            ->where('pi_contact_acknowledged', false)
            ->whereIn('status', [
                // শুধু এই একটি status এ modal আসবে
                // এর মানে: payment হয়েছে + PI accept করেছে — এখন victim কে contact করতে বলো
                'Private Investigator Assigned',
            ])
            ->whereExists(function ($q) {
                // Double check: payment সত্যিই হয়েছে কিনা
                $q->from('pi_payments')
                  ->whereColumn('pi_payments.complaint_id', 'complaints.complaint_id')
                  ->where('pi_payments.status', 'confirmed');
            })
            ->with('assignedPi:id,full_name,phone,email,pi_code,photo_url')
            ->get(['id', 'complaint_id', 'type', 'status', 'assigned_pi_id', 'pi_contact_acknowledged']);

        $pending = $complaints->map(function ($c) {
            if (!$c->assignedPi) return null;
            return [
                'complaint_id' => $c->complaint_id,
                'type'         => $c->type,
                'status'       => $c->status,
                'pi' => [
                    'name'      => $c->assignedPi->full_name,
                    'phone'     => $c->assignedPi->phone,
                    'email'     => $c->assignedPi->email,
                    'pi_code'   => $c->assignedPi->pi_code,
                    'photo_url' => $c->assignedPi->photo_url,
                ],
            ];
        })->filter()->values();

        return response()->json(['success' => true, 'pending' => $pending]);
    }

    // POST /api/pi/acknowledge-contact
    // Victim "OK, I'll contact PI" click করল → আর modal দেখাব না
    public function acknowledgePIContact(Request $request)
    {
        $request->validate(['complaint_id' => 'required|string']);

        $userId = $this->getAuthUserId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $myHash = hash_hmac('sha256', (string) $userId, config('app.key'));

        // Verify এটা সত্যিই এই user এর complaint
        $updated = Complaint::where('complaint_id', $request->complaint_id)
            ->where('anonymous_user_id', $myHash)
            ->where('is_anonymous', true)
            ->update(['pi_contact_acknowledged' => true]);

        return response()->json(['success' => true, 'updated' => $updated]);
    }


}