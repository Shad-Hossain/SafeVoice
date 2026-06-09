<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LawyerBid;
use App\Models\LawyerNotification;
use App\Helpers\BangladeshAreas;

class LegalRequestController extends Controller
{
    // ── POST /api/legal-request/submit ────────────────────────────
    // User একটা legal help request করে → সব active lawyer কে notification যায়
    public function submit(Request $request)
    {
        $isInstantRequest = $request->boolean('is_instant');

        $request->validate([
            'issue_type'          => 'required|string|max:100',
            'description'         => 'required|string|min:20|max:2000',
            'location'            => 'nullable|string|max:255',
            'preferred_city'      => 'nullable|string|max:100',
            'preferred_division'  => 'nullable|string|max:100',
            'preferred_district'  => 'nullable|string|max:100',
            'is_urgent'           => 'nullable|boolean',
            'is_instant'          => 'nullable|boolean',
            'budget_max'          => 'nullable|numeric|min:0',
            'user_phone'          => 'nullable|string|max:20',
            // Instant request এ deadline দরকার নেই — auto 30min set হয়
            'deadline'            => $isInstantRequest
                                     ? 'nullable'
                                     : 'required|date|after:+2 hours',
        ]);

        // Bug fix: sanctum auth থেকে প্রথমে try করো — middleware থাকলে এটাই কাজ করবে
        $userId    = null;
        $userName  = 'Anonymous';
        $userPhone = null;

        try {
            $u = $request->user();
            if ($u && isset($u->id)) {
                $userId    = (int) $u->id;
                $userName  = $u->name ?? 'Anonymous';
                $userPhone = $u->phone ?? null;
            }
        } catch (\Exception) {}

        // Fallback legacy: session বা query param
        if (!$userId) {
            $userId   = $request->session()->get('user_id');
            $userName = $request->session()->get('user_name', 'Anonymous');
        }

        // Web form fallback: Blade inject করা user_id (body তে আসে)
        if (!$userId && $request->filled('user_id')) {
            $userId   = (int) $request->input('user_id');
            $userName = $request->input('user_name', 'Anonymous');
        }

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Please log in first.'], 401);
        }

        if (!$userPhone && $request->filled('user_phone')) {
            $userPhone = preg_replace('/\D/', '', $request->user_phone);
            if (strlen($userPhone) === 13) $userPhone = substr($userPhone, 2);
        }

        $isInstant = $isInstantRequest;
        // Instant হলে deadline এখন থেকে ৩০ মিনিট (Emergency SOS), নাহলে user এর দেওয়া deadline
        $deadline = $isInstant ? now()->addHours(2) : \Carbon\Carbon::parse($request->deadline);

        // ── Document upload handle করো ─────────────────────────────
        $documentPaths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('legal_documents', 'public');
                    $documentPaths[] = $path;
                }
            }
        }

        $legalRequest = LegalRequest::create([
            'request_id'          => LegalRequest::generateRequestId(),
            'user_id'             => $userId,
            'user_name'           => $userName,
            'user_phone'          => $userPhone,
            'issue_type'          => $request->issue_type,
            'description'         => $request->description,
            'location'            => $request->location,
            'preferred_city'      => $request->preferred_city,
            'preferred_division'  => $request->preferred_division,
            // preferred_district না আসলে preferred_city থেকে নাও
            'preferred_district'  => $request->preferred_district ?? $request->preferred_city,
            'is_urgent'           => $request->boolean('is_urgent'),
            'is_instant'          => $isInstant,
            'budget_max'          => $request->budget_max,
            'deadline'            => $deadline,
            'document_paths'      => !empty($documentPaths) ? json_encode($documentPaths) : null,
            'status'              => 'open',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // ── Location-based lawyer routing ──────────────────────────────
        // User preferred_district বা preferred_division দিলে শুধু সেই area র lawyers পাবে।
        // কোনো preference না থাকলেই শুধু সবাইকে notify করা হবে।
        $preferredDistrict = $request->preferred_district ?? $request->preferred_city;
        $preferredDivision = $request->preferred_division;

        // Auto-detect division from district if not provided
        if ($preferredDistrict && !$preferredDivision) {
            $preferredDivision = BangladeshAreas::divisionOfDistrict($preferredDistrict);
        }

        $baseQuery = fn() => Lawyer::where('status', 'Active')->where('is_available', true);

        $lawyers = collect();

        if ($preferredDistrict) {
            // Tier 1: serving_areas এ preferred district explicitly আছে
            $lawyers = $baseQuery()
                ->whereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$preferredDistrict])
                ->get();

            // Tier 2: serving_areas match নেই কিন্তু lawyer এর city বা division match করে
            if ($lawyers->isEmpty() && $preferredDivision) {
                $lawyers = $baseQuery()
                    ->where(function ($q) use ($preferredDistrict, $preferredDivision) {
                        $q->where('city', $preferredDistrict)
                          ->orWhere('division', $preferredDivision);
                    })->get();
            }

            // District দেওয়া হয়েছে কিন্তু কোনো lawyer নেই — fallback নেই
            if ($lawyers->isEmpty()) {
                return response()->json([
                    'success'    => true,
                    'request_id' => $legalRequest->request_id,
                    'message'    => 'Request submitted! No lawyers currently available in ' . $preferredDistrict . '. You will be notified when one becomes available.',
                    'no_lawyers' => true,
                ]);
            }

        } elseif ($preferredDivision) {
            // Division দিয়েছে — ওই division এর সব district এর lawyers
            $divisionDistricts = BangladeshAreas::districtsOf($preferredDivision);
            $lawyers = $baseQuery()
                ->where(function ($q) use ($divisionDistricts, $preferredDivision) {
                    foreach ($divisionDistricts as $district) {
                        $q->orWhereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$district]);
                    }
                    $q->orWhere('division', $preferredDivision);
                })->get();

            if ($lawyers->isEmpty()) {
                return response()->json([
                    'success'    => true,
                    'request_id' => $legalRequest->request_id,
                    'message'    => 'Request submitted! No lawyers currently available in ' . $preferredDivision . ' division.',
                    'no_lawyers' => true,
                ]);
            }

        } else {
            // কোনো area preference নেই — সব active lawyer কে notify করো
            $lawyers = $baseQuery()->get();
        }

        $notifData = [
            'request_id'         => $legalRequest->request_id,
            'issue_type'         => $legalRequest->issue_type,
            'is_urgent'          => $legalRequest->is_urgent,
            'is_instant'         => $legalRequest->is_instant,
            'deadline'           => $legalRequest->deadline->toIso8601String(),
            'budget_max'         => $legalRequest->budget_max,
            'preferred_district' => $legalRequest->preferred_district,
            'preferred_division' => $legalRequest->preferred_division,
            'created_at'         => $legalRequest->created_at->toIso8601String(),
        ];

        $urgentPrefix = $isInstant ? '⚡ INSTANT: ' : ($legalRequest->is_urgent ? '🚨 URGENT: ' : '⚖️ New Legal Request: ');

        $notifs = [];
        foreach ($lawyers as $lawyer) {
            $notifs[] = [
                'lawyer_id'  => $lawyer->id,
                'type'       => 'new_request',
                'title'      => $urgentPrefix . ucfirst($legalRequest->issue_type),
                'body'       => substr($legalRequest->description, 0, 120) . '...',
                'data'       => json_encode($notifData),
                'is_read'    => false,
                'created_at' => now(),
            ];
        }

        if (!empty($notifs)) {
            DB::table('lawyer_notifications')->insert($notifs);
        }

        $this->sendFcmToLawyers($lawyers->pluck('id')->toArray(), $urgentPrefix . ucfirst($legalRequest->issue_type), substr($legalRequest->description, 0, 100));

        $locationHint = $preferredDistrict ? " in {$preferredDistrict}" : ($preferredDivision ? " in {$preferredDivision} division" : '');

        return response()->json([
            'success'            => true,
            'message'            => 'Your request has been sent to ' . count($notifs) . " available lawyers{$locationHint}.",
            'request_id'         => $legalRequest->request_id,
            'deadline'           => $legalRequest->deadline->toIso8601String(),
            'lawyers_notified'   => count($notifs),
            'preferred_district' => $preferredDistrict,
            'preferred_division' => $preferredDivision,
        ]);
    }

    // ── GET /api/legal-request/track/{requestId} ─────────────────
    // Public track — case ID দিয়ে status দেখবে
    public function track(Request $request, string $requestId)
    {
        $r = LegalRequest::with(['bids.lawyer:id,full_name,city,rating,experience_years,specializations,profile_photo', 'assignedLawyer:id,full_name,city,rating,profile_photo'])
            ->where('request_id', $requestId)
            ->first();

        if (!$r) return response()->json(['success' => false, 'message' => 'Case not found.'], 404);

        // Auto-expire check
        if ($r->isExpired() && in_array($r->status, ['open','bidding'])) {
            $r->update(['status' => 'expired', 'updated_at' => now()]);
            $r->refresh();
        }

        $bids = $r->bids->map(fn($b) => [
            'id'           => $b->id,
            'proposed_fee' => $b->proposed_fee,
            'commission'   => $b->platform_commission ?? null,
            'cover_note'   => $b->cover_note,
            'estimated_days'    => $b->estimated_days,
            'consultation_date' => $b->consultation_date?->toIso8601String(),
            'office_address'    => $b->office_address,
            'status'       => $b->status,
            'bid_at'       => $b->bid_at,
            'lawyer'       => $b->lawyer ? [
                'name'             => $b->lawyer->full_name,
                'city'             => $b->lawyer->city,
                'rating'           => $b->lawyer->rating,
                'experience_years' => $b->lawyer->experience_years,
                'specializations'  => $b->lawyer->specializations ?? [],
                'photo'            => $b->lawyer->profile_photo,
            ] : null,
        ]);

        return response()->json([
            'success' => true,
            'case'    => [
                ...$this->requestData($r),
                'bids' => $bids,
            ],
        ]);
    }

    // ── GET /api/legal-request/my-requests ────────────────────────
    // User এর নিজের সব requests দেখবে
    public function myRequests(Request $request)
    {
        $userId = $this->getUserId($request);
        if (!$userId) return response()->json(['success' => false], 401);

        $requests = LegalRequest::where('user_id', $userId)
            ->with([
                'bids.lawyer:id,full_name,profile_photo,city,rating,rating_count,experience_years,specializations,completed_cases',
                'assignedLawyer:id,full_name,profile_photo,city,phone,email',
                'acceptedBid:id,legal_request_id,office_address',
            ])
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
            ->whereNotIn('status', ['rejected']) // Bug fix: rejected bids লুকাও — refresh করলে আর দেখা যাবে না
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
            // 2% platform commission calculate
            $commission = round($bid->proposed_fee * 0.02, 2);

            // এই bid accept + commission store
            $bid->update([
                'status'              => 'accepted',
                'platform_commission' => $commission,
                'responded_at'        => now(),
            ]);

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

    // ── POST /api/legal-request/{requestId}/reject-bid ────────────
    // Pathao-style: user একটা bid reject করে → case open থাকে, পরের lawyer bid করতে পারে
    // সব bid reject হয়ে গেলে → user কে suggest করে budget বাড়িয়ে retry করতে
    public function rejectBid(Request $request, string $requestId)
    {
        $request->validate([
            'bid_id' => 'required|integer',
            'reason' => 'nullable|string|max:200',
        ]);

        $userId       = $this->getUserId($request);
        $legalRequest = LegalRequest::where('request_id', $requestId)->first();

        if (!$legalRequest)                        return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        if ($legalRequest->user_id !== $userId)    return response()->json(['success' => false], 403);
        if ($legalRequest->status === 'accepted')  return response()->json(['success' => false, 'message' => 'Already accepted a lawyer.'], 422);
        if ($legalRequest->status === 'cancelled') return response()->json(['success' => false, 'message' => 'Request is cancelled.'], 422);

        $bid = LawyerBid::find($request->bid_id);
        if (!$bid || $bid->legal_request_id !== $legalRequest->id) {
            return response()->json(['success' => false, 'message' => 'Bid not found.'], 404);
        }
        if ($bid->status === 'accepted') {
            return response()->json(['success' => false, 'message' => 'Cannot reject an already accepted bid.'], 422);
        }

        // Soft-reject এই bid টা — case cancel হয় না
        $bid->update([
            'status'       => 'rejected',
            'responded_at' => now(),
        ]);

        // Notify the lawyer
        LawyerNotification::create([
            'lawyer_id'  => $bid->lawyer_id,
            'type'       => 'bid_rejected',
            'title'      => 'Client declined your offer',
            'body'       => "Your bid for the {$legalRequest->issue_type} case was declined. The case is still open for other lawyers.",
            'data'       => ['request_id' => $legalRequest->request_id],
            'created_at' => now(),
        ]);

        // Check: are there any remaining non-rejected bids?
        $remainingBids = LawyerBid::where('legal_request_id', $legalRequest->id)
            ->whereNotIn('status', ['rejected'])
            ->count();

        // Case status back to 'open' so more lawyers can still bid
        if (in_array($legalRequest->status, ['bidding', 'open'])) {
            $legalRequest->update(['status' => 'open', 'updated_at' => now()]);
        }

        // Check: total lawyers available in this area who haven't bid yet
        $alreadyBidLawyerIds = LawyerBid::where('legal_request_id', $legalRequest->id)
            ->pluck('lawyer_id')->toArray();

        $preferredDistrict = $legalRequest->preferred_district;
        $preferredDivision = $legalRequest->preferred_division;

        $availableQuery = Lawyer::where('status', 'Active')->where('is_available', true)
            ->whereNotIn('id', $alreadyBidLawyerIds);

        if ($preferredDistrict) {
            $availableQuery->whereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$preferredDistrict]);
        } elseif ($preferredDivision) {
            $divDistricts = \App\Helpers\BangladeshAreas::districtsOf($preferredDivision);
            $availableQuery->where(function ($q) use ($divDistricts, $preferredDivision) {
                foreach ($divDistricts as $d) {
                    $q->orWhereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$d]);
                }
                $q->orWhere('division', $preferredDivision);
            });
        }

        $stillAvailable = $availableQuery->count();

        // All bids rejected AND no more lawyers available → suggest budget increase
        $allBidsRejected = LawyerBid::where('legal_request_id', $legalRequest->id)
            ->whereNotIn('status', ['rejected'])
            ->count() === 0;

        if ($allBidsRejected && $stillAvailable === 0) {
            $legalRequest->update(['status' => 'exhausted', 'updated_at' => now()]);
            return response()->json([
                'success'          => true,
                'bid_rejected'     => true,
                'all_rejected'     => true,
                'remaining_bids'   => 0,
                'lawyers_available'=> 0,
                'message'          => 'All lawyers have been reviewed. No more lawyers available in your area right now.',
                'suggestion'       => 'Try increasing your budget or expanding your area to attract more lawyers.',
            ]);
        }

        return response()->json([
            'success'           => true,
            'bid_rejected'      => true,
            'all_rejected'      => false,
            'remaining_bids'    => $remainingBids,
            'lawyers_available' => $stillAvailable,
            'message'           => 'Bid declined. The case remains open for other lawyers.',
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function getUserId(Request $request): ?int
    {
        // 1. Bearer token — manually check PAT table (middleware ছাড়াও কাজ করে)
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            try {
                $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
                if ($pat && $pat->tokenable_type === \App\Models\User::class) {
                    return (int) $pat->tokenable_id;
                }
            } catch (\Exception) {}
        }

        // 2. Session fallback (web dashboard)
        $id = $request->session()->get('user_id') ?? $request->query('user_id');
        return $id ? (int) $id : null;
    }

    private function requestData(LegalRequest $r): array
    {
        return [
            'id'              => $r->id,
            'request_id'      => $r->request_id,
            'issue_type'      => $r->issue_type,
            'description'     => $r->description,
            'location'        => $r->location,
            'preferred_city'  => $r->preferred_city,
            'is_urgent'       => $r->is_urgent,
            'is_instant'      => $r->is_instant,
            'budget_max'      => $r->budget_max,
            'deadline'        => $r->deadline?->toIso8601String(),
            'seconds_left'    => $r->secondsLeft(),
            'is_expired'      => $r->isExpired(),
            'status'          => $r->status,
            'bid_count'       => $r->bids ? $r->bids->count() : 0,
            'assigned_lawyer' => $r->assignedLawyer ? [
                'name'           => $r->assignedLawyer->full_name,
                'photo'          => $r->assignedLawyer->profile_photo,
                'city'           => $r->assignedLawyer->city,
                // Contact details — only meaningful after acceptance
                'phone'          => $r->status === 'accepted' ? $r->assignedLawyer->phone   : null,
                'email'          => $r->status === 'accepted' ? $r->assignedLawyer->email   : null,
                'office_address' => $r->status === 'accepted' ? ($r->acceptedBid?->office_address ?? null) : null,
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