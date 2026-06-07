<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LawyerBid;
use App\Models\LawyerNotification;

class LawyerDashboardController extends Controller
{
    // ── GET /api/lawyer/dashboard ──────────────────────────────────
    public function dashboard(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $activeBids   = LawyerBid::where('lawyer_id', $lawyer->id)
                            ->whereIn('status', ['pending','seen'])
                            ->with('legalRequest:id,request_id,issue_type,status,created_at,location,is_urgent')
                            ->orderByDesc('bid_at')->get();

        $acceptedCases = LawyerBid::where('lawyer_id', $lawyer->id)
                            ->where('status', 'accepted')
                            ->with('legalRequest:id,request_id,issue_type,status,created_at,user_name,user_phone')
                            ->orderByDesc('bid_at')->get();

        $openRequests = LegalRequest::where('status', 'open')
                            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
                            ->orderByDesc('is_urgent')
                            ->orderByDesc('created_at')
                            ->limit(10)
                            ->get()
                            ->map(fn($r) => [
                                'id'          => $r->id,
                                'request_id'  => $r->request_id,
                                'issue_type'  => $r->issue_type,
                                'description' => substr($r->description, 0, 150) . '...',
                                'location'    => $r->location,
                                'is_urgent'   => $r->is_urgent,
                                'budget_max'  => $r->budget_max,
                                'bid_count'   => $r->bids()->count(),
                                'created_at'  => $r->created_at,
                            ]);

        return response()->json([
            'success'       => true,
            'lawyer'        => $this->lawyerBasic($lawyer),
            'active_bids'   => $activeBids,
            'accepted_cases'=> $acceptedCases,
            'open_requests' => $openRequests,
            'stats' => [
                'total_bids'      => LawyerBid::where('lawyer_id', $lawyer->id)->count(),
                'accepted_bids'   => LawyerBid::where('lawyer_id', $lawyer->id)->where('status','accepted')->count(),
                'pending_bids'    => $activeBids->count(),
                'total_cases'     => $lawyer->total_cases,
                'completed_cases' => $lawyer->completed_cases,
                'rating'          => $lawyer->rating,
            ],
        ]);
    }

    // ── GET /api/lawyer/requests ───────────────────────────────────
    // সব open legal request দেখবে (bid করতে পারবে)
    public function openRequests(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $requests = LegalRequest::where('status', 'open')
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->withCount('bids')
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ── POST /api/lawyer/bid ───────────────────────────────────────
    // Lawyer একটা request এ bid করে (accept + price quote)
    public function placeBid(Request $request)
    {
        $request->validate([
            'request_id'        => 'required|string',
            'proposed_fee'      => 'required|numeric|min:100|max:500000',
            'cover_note'        => 'nullable|string|max:500',
            'estimated_days'    => 'nullable|integer|min:1|max:365',
            'consultation_date' => 'nullable|date|after:+5 hours',
            'office_address'    => 'nullable|string|max:300',
        ]);

        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        if (!$lawyer->is_available) {
            return response()->json(['success' => false, 'message' => 'You are currently set to unavailable. Enable availability from your dashboard first.'], 422);
        }

        $legalRequest = LegalRequest::where('request_id', $request->request_id)->first();
        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        if ($legalRequest->status !== 'open') return response()->json(['success' => false, 'message' => 'This request is no longer accepting bids.'], 422);

        $existing = LawyerBid::where('legal_request_id', $legalRequest->id)
                             ->where('lawyer_id', $lawyer->id)->first();
        if ($existing) return response()->json(['success' => false, 'message' => 'You already placed a bid on this request.'], 422);

        LawyerBid::create([
            'legal_request_id' => $legalRequest->id,
            'lawyer_id'        => $lawyer->id,
            'proposed_fee'     => $request->proposed_fee,
            'cover_note'       => $request->cover_note,
            'estimated_days'   => $request->estimated_days,
            'consultation_date'=> $request->consultation_date,
            'office_address'   => $request->office_address,
            'status'           => 'pending',
            'bid_at'           => now(),
        ]);

        // request status → bidding (কমপক্ষে ১টা bid এলে)
        if ($legalRequest->status === 'open') {
            $legalRequest->update(['status' => 'bidding', 'updated_at' => now()]);
        }

        // User কে notification (UserNotification table তে)
        if ($legalRequest->user_id) {
            DB::table('user_notifications')->insert([
                'user_id'    => $legalRequest->user_id,
                'type'       => 'lawyer_bid',
                'title'      => '⚖️ A lawyer responded to your request!',
                'body'       => "{$lawyer->full_name} offered ৳{$request->proposed_fee} for your {$legalRequest->issue_type} case.",
                'data'       => json_encode(['request_id' => $legalRequest->request_id, 'lawyer_id' => $lawyer->id]),
                'is_read'    => false,
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bid placed! The client will be notified.',
        ]);
    }

    // ── PUT /api/lawyer/bid/{bidId} ────────────────────────────────
    // Lawyer নিজের bid update করতে পারবে (accepted হওয়ার আগে)
    public function updateBid(Request $request, int $bidId)
    {
        $request->validate([
            'proposed_fee'   => 'nullable|numeric|min:100',
            'cover_note'     => 'nullable|string|max:500',
            'estimated_days' => 'nullable|integer|min:1',
        ]);

        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $bid = LawyerBid::where('id', $bidId)->where('lawyer_id', $lawyer->id)->first();
        if (!$bid) return response()->json(['success' => false, 'message' => 'Bid not found.'], 404);
        if ($bid->status === 'accepted') return response()->json(['success' => false, 'message' => 'Cannot edit an accepted bid.'], 422);

        $bid->update(array_filter([
            'proposed_fee'   => $request->proposed_fee,
            'cover_note'     => $request->cover_note,
            'estimated_days' => $request->estimated_days,
        ]));

        return response()->json(['success' => true, 'message' => 'Bid updated.']);
    }

    // ── GET /api/lawyer/notifications ─────────────────────────────
    public function notifications(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $notifs = LawyerNotification::where('lawyer_id', $lawyer->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // সব unread → read করে দাও
        LawyerNotification::where('lawyer_id', $lawyer->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success'       => true,
            'notifications' => $notifs,
        ]);
    }

    // ── GET /api/lawyer/notifications/unread-count ────────────────
    public function unreadCount(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['count' => 0]);
        return response()->json(['count' => $lawyer->getUnreadNotificationCount()]);
    }

    // ── POST /api/lawyer/toggle-availability ──────────────────────
    public function toggleAvailability(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $lawyer->update(['is_available' => !$lawyer->is_available]);

        return response()->json([
            'success'      => true,
            'is_available' => $lawyer->is_available,
            'message'      => $lawyer->is_available ? 'You are now available for new cases.' : 'You are now set to unavailable.',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function getAuthLawyer(Request $request): ?Lawyer
    {
        try {
            $user = $request->user('lawyer');
            if ($user instanceof Lawyer) return $user;
        } catch (\Exception) {}

        // Sanctum default guard fallback
        try {
            $user = $request->user();
            if ($user instanceof Lawyer) return $user;
        } catch (\Exception) {}

        $id = $request->session()->get('lawyer_id')
           ?? $request->input('lawyer_id')
           ?? $request->query('lawyer_id');

        return $id ? Lawyer::find($id) : null;
    }

    private function lawyerBasic(Lawyer $l): array
    {
        return [
            'id'           => $l->id,
            'lawyer_code'  => $l->lawyer_code,
            'full_name'    => $l->full_name,
            'profile_photo'=> $l->profile_photo,
            'city'         => $l->city,
            'is_available' => $l->is_available,
            'status'       => $l->status,
            'rating'       => $l->rating,
            'unread_notifications' => $l->getUnreadNotificationCount(),
        ];
    }
}
