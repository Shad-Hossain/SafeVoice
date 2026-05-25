<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\Officer;
use App\Models\User;
use App\Http\Controllers\FcmController;

class ComplaintController extends Controller
{
    // GET /api/complaints
    public function index(Request $request)
    {
        $query = Complaint::query()
            ->select('id','complaint_id','type','incident_date','location',
                     'description','is_anonymous','status','submitted_at','updated_at',
                     'assigned_pi_id','pi_assigned_at','payment_deadline','user_id',
                     'anonymous_user_id',
                     'legal_consent','publish_consent','admin_message',
                     'user_name as reporter_name');

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

    // GET /api/complaints/{id}
    public function show($id)
    {
        $complaint = Complaint::where('complaint_id', $id)
            ->select('id','complaint_id','type','incident_date','location',
                     'description','is_anonymous','status','submitted_at',
                     'updated_at','user_id','assigned_pi_id','pi_assigned_at','evidence_files',
                     'legal_consent','publish_consent')
            ->first();

        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
        }

        // Non-anonymous হলে submitter এর name ও email যোগ করো
        $submittedBy = null;
        if (!$complaint->is_anonymous && $complaint->user_id) {
            $user = User::where('id', $complaint->user_id)
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

        return response()->json([
            'success'      => true,
            'complaint'    => $complaint,
            'submitted_by' => $submittedBy,
        ]);
    }

    // POST /api/complaints/submit
    public function submit(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }

        // Probation বা Suspended user নতুন complaint করতে পারবে না
        $user = User::find($userId);
        if ($user && $user->status === 'Probation') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently on probation. You cannot submit new complaints until your pending evidence review is resolved.',
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

        $isAnonymous = $request->boolean('is_anonymous');

        $legalConsent   = $request->input('legal_consent');
        $publishConsent = $request->input('publish_consent');

        $status = 'Submitted';
        if ($legalConsent === 'no' && $publishConsent === 'no') {
            $status = 'Rejected';
        }

        $complaint = Complaint::create([
            'complaint_id'      => $complaintId,
            // Anonymous হলে user_id null — admin দেখবে না কে করেছে
            'user_id'           => $isAnonymous ? null : $userId,
            // anonymous_user_id সবসময় সেট — user নিজের complaint দেখতে ও notification পেতে পারবে
            'anonymous_user_id' => $userId,
            'type'              => $request->type,
            'incident_date'     => $incidentDate,
            'location'          => $request->location ?? '',
            'description'       => $request->description,
            'is_anonymous'      => $isAnonymous,
            'status'            => $status,
            'legal_consent'     => $legalConsent,
            'publish_consent'   => $publishConsent,
        ]);

        // Anonymous হলে complaints_count বাড়বে না (leaderboard এ দেখাবে না)
        if (!$isAnonymous) {
            User::where('id', $userId)->increment('complaints_count');
        }

        // সব complain submit এর notification পাঠাও (anonymous বা না)
        \App\Models\UserNotification::notify(
            $userId,
            'complaint_submitted',
            '📋 Complaint Submitted',
            "তোমার complaint {$complaint->complaint_id} সফলভাবে submit হয়েছে।" .
                ($isAnonymous ? ' (Anonymous — তুমি ছাড়া কেউ জানবে না)' : '') .
                ' আমরা শীঘ্রই review করব।',
            [
                'complaint_id' => $complaint->complaint_id,
                'action_url'   => '/track?id=' . $complaint->complaint_id,
                'icon'         => '📋',
            ]
        );

        return response()->json([
            'success'      => true,
            'complaint_id' => $complaintId,
            'message'      => 'Complaint submitted successfully',
        ]);
    }

    // POST /api/complaints/update-status
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
            return response()->json(['success' => true, 'message' => 'Status updated. Payment notification sent to user.']);
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
     * User কে notification পাঠাও — anonymous_user_id দিয়ে চেক করে
     * anonymous complaint এর user ও notification পাবে
     */
    private function notifyUser(Complaint $complaint, string $status): void
    {
        // anonymous_user_id আছে তাকেই notify করব (user_id না থাকলেও)
        $notifyUserId = $complaint->user_id ?? $complaint->anonymous_user_id ?? null;
        if (!$notifyUserId) return;

        $statusNotifMessages = [
            'Under Review'                  => ['icon' => '🔍', 'title' => '🔍 Complaint Under Review',           'msg' => "তোমার complaint {$complaint->complaint_id} এখন review এ আছে।"],
            'PI Notification Sent'          => ['icon' => '🕵️', 'title' => '🕵️ Private Investigator Notification', 'msg' => "তোমার complaint {$complaint->complaint_id} এর জন্য PI review শুরু হয়েছে।"],
            'PI Payment Confirmed'          => ['icon' => '💳', 'title' => '💳 Payment Confirmed',                 'msg' => "Payment confirmed। শীঘ্রই PI assign হবে।"],
            'Private Investigator Assigned' => ['icon' => '✅', 'title' => '✅ PI Assigned',                        'msg' => "তোমার complaint {$complaint->complaint_id} এ একজন Private Investigator assign হয়েছেন।"],
            'Resolved'                      => ['icon' => '🎉', 'title' => '🎉 Complaint Resolved',                 'msg' => "তোমার complaint {$complaint->complaint_id} resolve হয়েছে। ধন্যবাদ!"],
            'Rejected'                      => ['icon' => '❌', 'title' => '❌ Complaint Rejected',                 'msg' => "তোমার complaint {$complaint->complaint_id} process করা সম্ভব হয়নি। Support এ যোগাযোগ করো।"],
            'pi_search_started'             => ['icon' => '🔄', 'title' => '🔄 Investigator Search Started',       'msg' => "তোমার complaint {$complaint->complaint_id} এর জন্য Investigator খোঁজা শুরু হয়েছে।"],
        ];

        $notifInfo = $statusNotifMessages[$status] ?? null;
        if ($notifInfo) {
            \App\Models\UserNotification::notify(
                $notifyUserId,
                'status_update',
                $notifInfo['title'],
                $notifInfo['msg'],
                [
                    'complaint_id' => $complaint->complaint_id,
                    'action_url'   => '/track?id=' . $complaint->complaint_id,
                    'icon'         => $notifInfo['icon'],
                ]
            );
        }

        // FCM Push — শুধু non-anonymous user_id এর জন্য (privacy)
        if ($complaint->user_id) {
            $statusMessages = [
                'Submitted'                    => ['icon' => '📋', 'msg' => 'Your complaint has been received.'],
                'Under Review'                 => ['icon' => '🔍', 'msg' => 'Your complaint is now under review.'],
                'PI Notification Sent'         => ['icon' => '🕵️', 'msg' => 'A PI review has been initiated.'],
                'PI Payment Confirmed'         => ['icon' => '💳', 'msg' => 'Payment confirmed. PI will be assigned.'],
                'Private Investigator Assigned'=> ['icon' => '✅', 'msg' => 'A PI has been assigned to your complaint.'],
                'Resolved'                     => ['icon' => '🎉', 'msg' => 'Your complaint has been resolved.'],
                'Rejected'                     => ['icon' => '❌', 'msg' => 'Your complaint could not be processed.'],
            ];
            $info = $statusMessages[$status] ?? ['icon' => '🔔', 'msg' => 'Complaint status updated.'];
            FcmController::sendToUser(
                $complaint->user_id,
                $info['icon'] . ' Complaint ' . $complaint->complaint_id . ' — ' . $status,
                $info['msg'],
                ['type' => 'status_update', 'complaint_id' => $complaint->complaint_id, 'status' => $status, 'url' => '/track?id=' . $complaint->complaint_id]
            );
        }
    }

    // GET /api/my-complaints
    public function myComplaints(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // anonymous_user_id দিয়ে খুঁজবে — anonymous complaint ও দেখাবে
        // কিন্তু admin panel এ user_id = null থাকায় admin জানবে না কে করেছে
        $complaints = Complaint::where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('anonymous_user_id', $userId);
            })
            ->orderByDesc('submitted_at')
            ->get();

        return response()->json(['success' => true, 'complaints' => $complaints]);
    }

    // GET /api/track_complaint?id=SV-2026-XXXX
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

        return response()->json([
            'success'   => true,
            'complaint' => [
                'complaint_id' => $complaint->complaint_id,
                'type'         => $complaint->type,
                'location'     => $complaint->location,
                'status'       => $complaint->status,
                'is_anonymous' => $complaint->is_anonymous,
                'submitted_at' => $complaint->submitted_at,
                'incident_date'=> $complaint->incident_date,
            ],
        ]);
    }
}