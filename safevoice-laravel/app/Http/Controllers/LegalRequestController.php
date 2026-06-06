<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LawyerBid;
use App\Models\LawyerNotification;

class LegalRequestController extends Controller
{
    // ── POST /api/legal-request/submit ────────────────────────────
    // User একটা legal help request করে → সব active lawyer কে notification যায়
    public function submit(Request $request)
    {
        $request->validate([
            'issue_type'      => 'required|string|max:100',
            'description'     => 'required|string|min:20|max:2000',
            'location'        => 'nullable|string|max:255',
            'preferred_city'  => 'nullable|string|max:100',
            'is_urgent'       => 'nullable|boolean',
            'budget_max'      => 'nullable|numeric|min:0',
            'user_phone'      => 'nullable|string|max:20',
        ]);

        // user identify
        $userId   = null;
        $userName = 'Anonymous';
        $userPhone= null;

        try { $u = $request->user(); if ($u) { $userId = $u->id; $userName = $u->name; $userPhone = $u->phone; } } catch (\Exception) {}
        if (!$userId) { $userId = $request->session()->get('user_id'); $userName = $request->session()->get('user_name', 'Anonymous'); }

        // request create
        // user_phone: from auth user OR from request input
        if (!$userPhone && $request->filled('user_phone')) {
            $userPhone = preg_replace('/\D/', '', $request->user_phone);
            if (strlen($userPhone) === 13) $userPhone = substr($userPhone, 2);
        }

        $legalRequest = LegalRequest::create([
            'request_id'     => LegalRequest::generateRequestId(),
            'user_id'        => $userId,
            'user_name'      => $userName,
            'user_phone'     => $userPhone,
            'issue_type'     => $request->issue_type,
            'description'    => $request->description,
            'location'       => $request->location,
            'preferred_city' => $request->preferred_city,
            'is_urgent'      => $request->boolean('is_urgent'),
            'budget_max'     => $request->budget_max,
            'status'         => 'open',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ── Broadcast: সব Active + Available lawyer কে notification দাও ──
        // preferred_city থাকলে শুধু ওই district এর lawyer দের notify করো
        $lawyerQuery = Lawyer::where('status', 'Active')->where('is_available', true);
        if ($request->filled('preferred_city')) {
            $lawyerQuery->where('city', 'LIKE', '%' . $request->preferred_city . '%');
        }
        $lawyers = $lawyerQuery->get();

        // কোনো lawyer না পেলে সব lawyer কে notify করো (fallback)
        if ($lawyers->isEmpty()) {
            $lawyers = Lawyer::where('status', 'Active')->where('is_available', true)->get();
        }

        $notifData = [
            'request_id'  => $legalRequest->request_id,
            'issue_type'  => $legalRequest->issue_type,
            'is_urgent'   => $legalRequest->is_urgent,
            'location'    => $legalRequest->location,
            'budget_max'  => $legalRequest->budget_max,
            'created_at'  => $legalRequest->created_at->toIso8601String(),
        ];

        $notifs = [];
        foreach ($lawyers as $lawyer) {
            $notifs[] = [
                'lawyer_id'  => $lawyer->id,
                'type'       => 'new_request',
                'title'      => ($legalRequest->is_urgent ? '🚨 URGENT: ' : '⚖️ New Legal Request: ') . ucfirst($legalRequest->issue_type),
                'body'       => substr($legalRequest->description, 0, 120) . '...',
                'data'       => json_encode($notifData),
                'is_read'    => false,
                'created_at' => now(),
            ];
        }

        if (!empty($notifs)) {
            DB::table('lawyer_notifications')->insert($notifs);
        }

        // FCM push (if tokens exist) — fire and forget
        $this->sendFcmToLawyers($lawyers->pluck('id')->toArray(), $notifData['title'] ?? '⚖️ New Legal Request', substr($legalRequest->description, 0, 100));

        return response()->json([
            'success'    => true,
            'message'    => 'Your request has been sent to ' . count($notifs) . ' available lawyers. You will be notified when they respond.',
            'request_id' => $legalRequest->request_id,
            'lawyers_notified' => count($notifs),
        ]);
    }

    // ── GET /api/legal-request/my-requests ────────────────────────
    // User এর নিজের সব requests দেখবে
    public function myRequests(Request $request)
    {
        $userId = $this->getUserId($request);
        if (!$userId) return response()->json(['success' => false], 401);

        $requests = LegalRequest::where('user_id', $userId)
            ->with(['bids.lawyer:id,full_name,profile_photo,city,rating,experience_years,specializations', 'assignedLawyer:id,full_name,profile_photo,city,rating'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => $this->requestData($r));

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ── GET /api/legal-request/{requestId}/bids ───────────────────
    // User একটা request এর সব lawyer bid দেখবে
    public function getBids(Request $request, string $requestId)
    {
        $userId = $this->getUserId($request);
        $legalRequest = LegalRequest::where('request_id', $requestId)->first();

        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        if ($legalRequest->user_id && $legalRequest->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // unseen bids গুলো seen করে দাও
        LawyerBid::where('legal_request_id', $legalRequest->id)
                 ->where('status', 'pending')
                 ->update(['status' => 'seen']);

        $bids = LawyerBid::where('legal_request_id', $legalRequest->id)
            ->with('lawyer:id,full_name,profile_photo,city,rating,rating_count,experience_years,specializations,bar_council_id,completed_cases')
            ->orderBy('proposed_fee')
            ->get()
            ->map(fn($b) => [
                'id'            => $b->id,
                'lawyer_id'     => $b->lawyer_id,
                'lawyer'        => [
                    'name'          => $b->lawyer->full_name,
                    'photo'         => $b->lawyer->profile_photo,
                    'city'          => $b->lawyer->city,
                    'rating'        => $b->lawyer->rating,
                    'rating_count'  => $b->lawyer->rating_count,
                    'experience'    => $b->lawyer->experience_years,
                    'specializations'=> $b->lawyer->specializations ?? [],
                    'completed_cases'=> $b->lawyer->completed_cases,
                    'bar_council_id' => $b->lawyer->bar_council_id,
                ],
                'proposed_fee'  => $b->proposed_fee,
                'cover_note'    => $b->cover_note,
                'estimated_days'=> $b->estimated_days,
                'status'        => $b->status,
                'bid_at'        => $b->bid_at,
            ]);

        return response()->json([
            'success' => true,
            'bids'    => $bids,
            'request' => $this->requestData($legalRequest),
        ]);
    }

    // ── POST /api/legal-request/{requestId}/accept-bid ────────────
    // User একজন lawyer এর bid accept করে
    public function acceptBid(Request $request, string $requestId)
    {
        $request->validate(['bid_id' => 'required|integer']);

        $userId = $this->getUserId($request);
        $legalRequest = LegalRequest::where('request_id', $requestId)->first();

        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        if ($legalRequest->user_id !== $userId) return response()->json(['success' => false], 403);
        if ($legalRequest->status === 'accepted') return response()->json(['success' => false, 'message' => 'You already accepted a lawyer.'], 422);

        $bid = LawyerBid::find($request->bid_id);
        if (!$bid || $bid->legal_request_id !== $legalRequest->id) {
            return response()->json(['success' => false, 'message' => 'Bid not found.'], 404);
        }

        DB::transaction(function () use ($bid, $legalRequest) {
            // এই bid accept
            $bid->update(['status' => 'accepted', 'responded_at' => now()]);

            // বাকি সব bid reject
            LawyerBid::where('legal_request_id', $legalRequest->id)
                     ->where('id', '!=', $bid->id)
                     ->update(['status' => 'rejected', 'responded_at' => now()]);

            // request update
            $legalRequest->update([
                'status'             => 'accepted',
                'assigned_lawyer_id' => $bid->lawyer_id,
                'accepted_at'        => now(),
                'updated_at'         => now(),
            ]);

            // accepted lawyer কে notification
            LawyerNotification::create([
                'lawyer_id'  => $bid->lawyer_id,
                'type'       => 'bid_accepted',
                'title'      => '🎉 Your bid was accepted!',
                'body'       => "Client accepted your offer of ৳{$bid->proposed_fee} for their {$legalRequest->issue_type} case.",
                'data'       => ['request_id' => $legalRequest->request_id, 'fee' => $bid->proposed_fee],
                'created_at' => now(),
            ]);

            // rejected lawyers কে notification
            $rejectedLawyerIds = LawyerBid::where('legal_request_id', $legalRequest->id)
                ->where('id', '!=', $bid->id)->pluck('lawyer_id');

            foreach ($rejectedLawyerIds as $lawyerId) {
                LawyerNotification::create([
                    'lawyer_id'  => $lawyerId,
                    'type'       => 'bid_rejected',
                    'title'      => 'Client chose another lawyer',
                    'body'       => "Your bid for the {$legalRequest->issue_type} case was not selected.",
                    'data'       => ['request_id' => $legalRequest->request_id],
                    'created_at' => now(),
                ]);
            }

            // lawyer total_cases +1
            Lawyer::where('id', $bid->lawyer_id)->increment('total_cases');
        });

        return response()->json([
            'success' => true,
            'message' => 'Lawyer selected! They have been notified and will contact you soon.',
        ]);
    }

    // ── POST /api/legal-request/{requestId}/cancel ────────────────
    public function cancel(Request $request, string $requestId)
    {
        $userId = $this->getUserId($request);
        $legalRequest = LegalRequest::where('request_id', $requestId)
                                    ->where('user_id', $userId)->first();
        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        if (in_array($legalRequest->status, ['completed', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot cancel.'], 422);
        }
        $legalRequest->update(['status' => 'cancelled', 'updated_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Request cancelled.']);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function getUserId(Request $request): ?int
    {
        try { $u = $request->user(); if ($u) return $u->id; } catch (\Exception) {}
        return $request->session()->get('user_id') ?? $request->query('user_id');
    }

    private function requestData(LegalRequest $r): array
    {
        return [
            'id'              => $r->id,
            'request_id'      => $r->request_id,
            'issue_type'      => $r->issue_type,
            'description'     => $r->description,
            'location'        => $r->location,
            'is_urgent'       => $r->is_urgent,
            'budget_max'      => $r->budget_max,
            'status'          => $r->status,
            'bid_count'       => $r->bids ? $r->bids->count() : 0,
            'assigned_lawyer' => $r->assignedLawyer ? [
                'name'  => $r->assignedLawyer->full_name,
                'photo' => $r->assignedLawyer->profile_photo,
                'city'  => $r->assignedLawyer->city,
            ] : null,
            'created_at'      => $r->created_at,
        ];
    }

    private function sendFcmToLawyers(array $lawyerIds, string $title, string $body): void
    {
        // FCM tokens টেবিল থেকে পাঠাও
        try {
            $tokens = DB::table('lawyer_fcm_tokens')
                ->whereIn('lawyer_id', $lawyerIds)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) return;

            $serverKey = env('FCM_SERVER_KEY', '');
            if (!$serverKey) return;

            $data = json_encode([
                'registration_ids' => $tokens,
                'notification'     => ['title' => $title, 'body' => $body, 'sound' => 'default'],
                'data'             => ['type' => 'new_legal_request'],
                'priority'         => 'high',
            ]);

            $ch = curl_init('https://fcm.googleapis.com/fcm/send');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Authorization: key=' . $serverKey, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Lawyer FCM error: ' . $e->getMessage());
        }
    }
}