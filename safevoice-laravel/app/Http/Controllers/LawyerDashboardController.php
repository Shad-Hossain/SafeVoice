<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LawyerBid;
use App\Models\LawyerNotification;
use Laravel\Sanctum\PersonalAccessToken;

class LawyerDashboardController extends Controller
{
    // ── GET /api/lawyer/dashboard ──────────────────────────────────
    public function dashboard(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $activeBids = LawyerBid::where('lawyer_id', $lawyer->id)
            ->whereIn('status', ['pending', 'seen'])
            ->with('legalRequest:id,request_id,issue_type,status,created_at,location,is_urgent,is_instant,deadline')
            ->orderByDesc('bid_at')->get();

        $acceptedCases = LawyerBid::where('lawyer_id', $lawyer->id)
            ->where('status', 'accepted')
            ->with([
                'legalRequest:id,request_id,issue_type,status,created_at,user_name,user_phone,is_instant',
                'legalRequest.payment:id,legal_request_id,gross_amount,status,payment_deadline,paid_claimed_at',
            ])
            ->orderByDesc('bid_at')->get()
            ->map(function ($bid) {
                // Flatten payment into bid object for easy frontend access
                $bid->payment = $bid->legalRequest?->payment ?? null;
                return $bid;
            });

        // ── ⚡ Instant Requests (30-min Emergency — SOS style) ─────────
        // Lawyer এর serving_areas এর মধ্যে যেসব instant request এখনো live
        $instantQuery = LegalRequest::whereIn('status', ['open', 'bidding'])  // 'bidding' ও দেখাবে — প্রথম bid এলেই status change হয়
            ->where('is_instant', true)
            ->where('deadline', '>', now())
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->withCount('bids')
            ->orderByDesc('created_at');

        // Area filtering — lawyer এর serving_areas এ যে districts আছে শুধু সেই requests দেখাবে
        // $lawyerAreas empty হলে lawyer এর city/division দিয়ে filter করবে
        $lawyerAreas    = $lawyer->serving_areas ?? [];
        $lawyerCity     = $lawyer->city;
        $lawyerDivision = $lawyer->division;

        $areaFilter = function ($q) use ($lawyerAreas, $lawyerCity, $lawyerDivision) {
            $q->where(function ($inner) use ($lawyerAreas, $lawyerCity, $lawyerDivision) {
                // Requests with no area preference — visible to everyone
                $inner->whereNull('preferred_district')
                      ->whereNull('preferred_division');
            })->orWhere(function ($inner) use ($lawyerAreas, $lawyerCity, $lawyerDivision) {
                // Requests with area preference — only match lawyer's areas
                if (!empty($lawyerAreas)) {
                    foreach ($lawyerAreas as $area) {
                        $inner->orWhere('preferred_district', $area);
                    }
                }
                if ($lawyerCity) {
                    $inner->orWhere('preferred_district', $lawyerCity);
                }
                if ($lawyerDivision) {
                    $inner->orWhere('preferred_division', $lawyerDivision);
                }
            });
        };

        $instantQuery->where($areaFilter);

        $instantRequests = $instantQuery->get()
            ->map(fn($r) => $this->requestPreview($r, urgent: true));

        // ── 📅 Scheduled Requests ─────────────────────────────────────
        $scheduledQuery = LegalRequest::whereIn('status', ['open', 'bidding'])  // 'bidding' ও include করো
            ->where('is_instant', false)
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->withCount('bids')
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->limit(15);

        $scheduledQuery->where($areaFilter);

        $scheduledRequests = $scheduledQuery->get()
            ->map(fn($r) => $this->requestPreview($r));

        return response()->json([
            'success'            => true,
            'lawyer'             => $this->lawyerBasic($lawyer),
            'instant_requests'   => $instantRequests,   // ⚡ Emergency tab — 30min SOS
            'scheduled_requests' => $scheduledRequests, // 📅 Scheduled tab
            'active_bids'        => $activeBids,
            'accepted_cases'     => $acceptedCases,
            'stats' => [
                'total_bids'          => LawyerBid::where('lawyer_id', $lawyer->id)->count(),
                'accepted_bids'       => LawyerBid::where('lawyer_id', $lawyer->id)->where('status', 'accepted')->count(),
                'pending_bids'        => $activeBids->count(),
                'total_cases'         => $lawyer->total_cases,
                'completed_cases'     => $lawyer->completed_cases,
                'rating'              => $lawyer->rating,
                'instant_pending'     => $instantRequests->count(),
                'scheduled_pending'   => $scheduledRequests->count(),
            ],
        ]);
    }

    // ── GET /api/lawyer/requests ───────────────────────────────────
    // সব open legal request দেখবে (bid করতে পারবে)
    public function openRequests(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $requests = LegalRequest::whereIn('status', ['open', 'bidding'])
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->withCount('bids')
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ── GET /api/lawyer/requests/instant ──────────────────────────
    // ⚡ শুধু instant (30-min SOS-style) requests — আলাদা dashboard
    public function instantRequests(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $requests = LegalRequest::whereIn('status', ['open', 'bidding'])  // 'bidding' ও include
            ->where('is_instant', true)
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->where(function ($q) {
                // expired instant requests দেখাবে না
                $q->whereNull('deadline')->orWhere('deadline', '>', now());
            })
            ->withCount('bids')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => $this->requestPreview($r, urgent: true));

        return response()->json([
            'success'  => true,
            'type'     => 'instant',
            'count'    => $requests->count(),
            'requests' => $requests,
        ]);
    }

    // ── GET /api/lawyer/requests/scheduled ────────────────────────
    // 📅 শুধু scheduled (normal deadline) requests — আলাদা dashboard
    public function scheduledRequests(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $requests = LegalRequest::whereIn('status', ['open', 'bidding'])  // 'bidding' ও include
            ->where('is_instant', false)
            ->whereDoesntHave('bids', fn($q) => $q->where('lawyer_id', $lawyer->id))
            ->withCount('bids')
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->paginate(20);

        $items = $requests->getCollection()->map(fn($r) => $this->requestPreview($r));
        $requests->setCollection($items);

        return response()->json([
            'success'  => true,
            'type'     => 'scheduled',
            'requests' => $requests,
        ]);
    }

    // ── POST /api/lawyer/bid ───────────────────────────────────────
    // Lawyer একটা request এ bid করে (accept + price quote)
    public function placeBid(Request $request)
    {
        // Bug fix: validation failure কে JSON return করো, redirect না
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'request_id'     => 'required|string',
            'proposed_fee'   => 'required|numeric|min:100|max:500000',
            'cover_note'     => 'nullable|string|max:500',
            'office_address' => 'required|string|max:300',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        if (!$lawyer->is_available) {
            return response()->json(['success' => false, 'message' => 'You are currently set to unavailable. Enable availability from your dashboard first.'], 422);
        }

        $legalRequest = LegalRequest::where('request_id', $request->request_id)->first();
        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        if (!in_array($legalRequest->status, ['open', 'bidding'])) return response()->json(['success' => false, 'message' => 'This request is no longer accepting bids.'], 422);

        // Bug fix: deadline পার হয়ে গেলে bid করা যাবে না
        if ($legalRequest->deadline && now()->greaterThan($legalRequest->deadline)) {
            $legalRequest->update(['status' => 'expired', 'updated_at' => now()]);
            return response()->json(['success' => false, 'message' => 'Bidding deadline has passed. This case is no longer accepting bids.'], 422);
        }

        $existing = LawyerBid::where('legal_request_id', $legalRequest->id)
                             ->where('lawyer_id', $lawyer->id)->first();
        if ($existing) return response()->json(['success' => false, 'message' => 'You already placed a bid on this request.'], 422);

        LawyerBid::create([
            'legal_request_id' => $legalRequest->id,
            'lawyer_id'        => $lawyer->id,
            'proposed_fee'     => $request->proposed_fee,
            'cover_note'       => $request->cover_note,
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
                'message'    => "{$lawyer->full_name} offered ৳{$request->proposed_fee} for your {$legalRequest->issue_type} case.",
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
        // 1. Session (web dashboard login করলে এটাই কাজ করে)
        $id = $request->session()->get('lawyer_id');
        if ($id) return Lawyer::find($id);

        // 2. Bearer token — manually hash করে PAT table এ খোঁজো
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            try {
                $pat = PersonalAccessToken::findToken($bearerToken);
                if ($pat && $pat->tokenable_type === Lawyer::class) {
                    return Lawyer::find($pat->tokenable_id);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('getAuthLawyer token error: ' . $e->getMessage());
            }
        }

        // 3. Query param fallback
        $qid = $request->query('lawyer_id') ?? $request->input('lawyer_id');
        return $qid ? Lawyer::find($qid) : null;
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

    /**
     * Lawyer কে দেখানোর জন্য একটা request এর preview।
     * Instant dashboard এ seconds_left + minutes_left দেখায়।
     */
    private function requestPreview(LegalRequest $r, bool $urgent = false): array
    {
        // document_paths decode করো
        $docs = [];
        if ($r->document_paths) {
            $decoded = is_array($r->document_paths) ? $r->document_paths : json_decode($r->document_paths, true);
            $docs = is_array($decoded) ? $decoded : [];
        }

        $data = [
            'id'             => $r->id,
            'request_id'     => $r->request_id,
            'issue_type'     => $r->issue_type,
            'description'    => substr($r->description, 0, 150) . '...',
            'location'       => $r->location,
            'district'       => $r->preferred_district,
            'division'       => $r->preferred_division,
            'is_urgent'      => $r->is_urgent,
            'is_instant'     => $r->is_instant,
            'budget_max'     => $r->budget_max,
            'deadline'       => $r->deadline?->toIso8601String(),
            'bid_count'      => $r->bids_count ?? 0,
            'document_paths' => $docs,
            'created_at'     => $r->created_at,
        ];

        if ($urgent || $r->is_instant) {
            $data['seconds_left'] = $r->secondsLeft();
            $data['minutes_left'] = $r->deadline
                ? max(0, (int) now()->diffInMinutes($r->deadline, false))
                : null;
        }

        return $data;
    }
}