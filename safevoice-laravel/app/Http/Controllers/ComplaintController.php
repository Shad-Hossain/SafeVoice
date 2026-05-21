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

        return response()->json(['success' => true, 'complaint' => $complaint]);
    }

    // POST /api/complaints/submit
    public function submit(Request $request)
    {
        // Session থেকে user_id নাও, না পেলে request body থেকে নাও
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
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

        $legalConsent   = $request->input('legal_consent');    // 'yes' | 'no' | null
        $publishConsent = $request->input('publish_consent');  // 'yes' | 'no' | null

        // দুটোতেই 'no' দিলে → auto Rejected
        $status = 'Submitted';
        if ($legalConsent === 'no' && $publishConsent === 'no') {
            $status = 'Rejected';
        }

        $complaint = Complaint::create([
            'complaint_id'   => $complaintId,
            'user_id'        => $isAnonymous ? null : $userId,
            'type'           => $request->type,
            'incident_date'  => $incidentDate,
            'location'       => $request->location ?? '',
            'description'    => $request->description,
            'is_anonymous'   => $isAnonymous,
            'status'         => $status,
            'legal_consent'  => $legalConsent,
            'publish_consent'=> $publishConsent,
        ]);

        // Anonymous হলে count বাড়বে না — admin track করতে পারবে না
        if (!$isAnonymous) {
            User::where('id', $userId)->increment('complaints_count');
        }

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

            // FCM Push — PI assigned notification
            if ($complaint->user_id) {
                FcmController::sendToUser(
                    $complaint->user_id,
                    '✅ Private Investigator Assigned',
                    'A Private Investigator has been assigned to your complaint ' . $complaint->complaint_id . '. Investigation is underway.',
                    ['type' => 'status_update', 'complaint_id' => $complaint->complaint_id, 'status' => 'Private Investigator Assigned', 'url' => '/track?id=' . $complaint->complaint_id]
                );
            }

            return response()->json(['success' => true, 'message' => 'Status updated. Payment notification sent to user.']);
        }

        $oldStatus = $complaint->status;

        $complaint->update([
            'status'        => $request->status,
            'admin_message' => $request->input('admin_message', ''),
        ]);

        // Case Resolved বা Rejected হলে assigned PI এর active_cases কমাও
        // (total_cases assign এর সময়ই বেড়েছে, সেটা আর কমবে না)
        if (in_array($request->status, ['Resolved', 'Rejected'])
            && !in_array($oldStatus, ['Resolved', 'Rejected'])
            && $complaint->assigned_pi_id)
        {
            \App\Models\PrivateInvestigator::where('id', $complaint->assigned_pi_id)
                ->where('active_cases', '>', 0)
                ->decrement('active_cases');
        }

        // FCM Push — status change notification to user
        if ($complaint->user_id) {
            $statusMessages = [
                'Submitted'                    => ['icon' => '📋', 'msg' => 'Your complaint has been received and is being reviewed.'],
                'Under Review'                 => ['icon' => '🔍', 'msg' => 'Your complaint is now under review by our team.'],
                'PI Notification Sent'         => ['icon' => '🕵️', 'msg' => 'A Private Investigator review has been initiated for your complaint.'],
                'PI Payment Confirmed'         => ['icon' => '💳', 'msg' => 'Your payment has been confirmed. PI will be assigned shortly.'],
                'Private Investigator Assigned'=> ['icon' => '✅', 'msg' => 'A Private Investigator has been assigned to your complaint.'],
                'Resolved'                     => ['icon' => '🎉', 'msg' => 'Your complaint has been resolved. Thank you for reporting.'],
                'Rejected'                     => ['icon' => '❌', 'msg' => 'Your complaint could not be processed. Please contact support for details.'],
            ];

            $info = $statusMessages[$request->status] ?? ['icon' => '🔔', 'msg' => 'Your complaint status has been updated.'];

            FcmController::sendToUser(
                $complaint->user_id,
                $info['icon'] . ' Complaint ' . $complaint->complaint_id . ' — ' . $request->status,
                $info['msg'],
                ['type' => 'status_update', 'complaint_id' => $complaint->complaint_id, 'status' => $request->status, 'url' => '/track?id=' . $complaint->complaint_id]
            );
        }

        return response()->json(['success' => true, 'message' => 'Status updated to ' . $request->status]);
       
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

    $complaints = Complaint::where('user_id', $userId)
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