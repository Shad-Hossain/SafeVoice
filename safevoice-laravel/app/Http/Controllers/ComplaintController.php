<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\Officer;
use App\Models\User;

class ComplaintController extends Controller
{
    // GET /api/complaints
    public function index(Request $request)
    {
        $query = Complaint::query()
            ->select('id','complaint_id','type','incident_date','location',
                     'description','is_anonymous','status','submitted_at','updated_at');

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
                     'updated_at','user_id','assigned_pi_id','pi_assigned_at','evidence_files')
            ->first();

        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
        }

        return response()->json(['success' => true, 'complaint' => $complaint]);
    }

    // POST /api/complaints/submit
    public function submit(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }

        $request->validate([
            'type'        => 'required|string',
            'description' => 'required|string',
        ]);

        $userId      = $request->session()->get('user_id');
        $complaintId = 'SV-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $incidentDate = null;
        if ($request->filled('incident_date')) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $request->incident_date);
            if ($dt) $incidentDate = $dt->format('Y-m-d H:i:s');
        }

        $complaint = Complaint::create([
            'complaint_id' => $complaintId,
            'user_id'      => $userId,
            'type'         => $request->type,
            'incident_date'=> $incidentDate,
            'location'     => $request->location ?? '',
            'description'  => $request->description,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'status'       => 'Submitted',
        ]);

        User::where('id', $userId)->increment('complaints_count');

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

        // Blind officer assignment
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

            return response()->json(['success' => true, 'message' => 'Status updated. Payment notification sent to user.']);
        }

        $complaint->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => 'Status updated to ' . $request->status]);
    }

    // GET /api/my-complaints
    public function myComplaints(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $complaints = Complaint::where('user_id', $request->session()->get('user_id'))
            ->orderByDesc('submitted_at')
            ->get();

        return response()->json(['success' => true, 'complaints' => $complaints]);
    }
}
