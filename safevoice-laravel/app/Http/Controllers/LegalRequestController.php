<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LegalRequest;
use App\Models\LegalRequestOffer;
use App\Models\LegalPayment;
use App\Models\Lawyer;
use App\Models\User;

class LegalRequestController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // POST /api/legal/request
    //
    // User legal request create করবে
    // Body: case_type, description, user_budget, deadline_hours
    // Files: documents[] (optional, multiple)
    // ══════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }

        $request->validate([
            'case_type'      => 'required|string|max:100',
            'description'    => 'required|string|min:20',
            'user_budget'    => 'required|numeric|min:100',
            'deadline_hours' => 'required|integer|min:1|max:720', // max 30 দিন
        ]);

        // ── Documents upload ──────────────────────────────────────────
        $documentPaths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $doc) {
                $ext      = strtolower($doc->getClientOriginalExtension());
                $allowed  = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                if (!in_array($ext, $allowed)) continue;

                $filename = 'legal_doc_' . uniqid() . '.' . $ext;
                $doc->move(public_path('uploads/legal_docs'), $filename);
                $documentPaths[] = 'uploads/legal_docs/' . $filename;
            }
        }

        // ── Unique request code ───────────────────────────────────────
        $code = 'LR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

        $legalRequest = LegalRequest::create([
            'request_code'   => $code,
            'user_id'        => $user->id,
            'case_type'      => $request->case_type,
            'description'    => $request->description,
            'documents'      => $documentPaths ?: null,
            'user_budget'    => $request->user_budget,
            'deadline_hours' => $request->deadline_hours,
            'status'         => 'open',
        ]);

        // ── সব approved lawyer দের FCM notification পাঠাও ─────────────
        $this->notifyAllLawyers($legalRequest);

        return response()->json([
            'success'        => true,
            'message'        => 'Legal request submitted! Lawyers will respond shortly.',
            'request_code'   => $code,
            'legal_request'  => $this->formatRequest($legalRequest),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/legal/my-requests
    //
    // User এর সব legal requests দেখবে
    // ══════════════════════════════════════════════════════════════════
    public function myRequests(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $requests = LegalRequest::where('user_id', $user->id)
            ->with(['acceptedLawyer', 'offers.lawyer'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => $this->formatRequest($r));

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/legal/request/{id}
    //
    // Single request detail — সব offers সহ
    // ══════════════════════════════════════════════════════════════════
    public function show(Request $request, int $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $legalRequest = LegalRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['acceptedLawyer', 'offers.lawyer', 'payments', 'caseUpdates.lawyer'])
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        // Active offers (rejected action বাদে)
        $offers = $legalRequest->offers
            ->where('action', '!=', 'rejected')
            ->map(fn($o) => $this->formatOffer($o));

        return response()->json([
            'success'       => true,
            'legal_request' => $this->formatRequest($legalRequest),
            'offers'        => $offers->values(),
            'case_updates'  => $legalRequest->caseUpdates->map(fn($u) => [
                'id'            => $u->id,
                'note'          => $u->note,
                'status_change' => $u->status_change,
                'lawyer_name'   => $u->lawyer->display_name ?? $u->lawyer->name_from_id,
                'created_at'    => $u->created_at,
            ]),
            'payments'      => $legalRequest->payments->map(fn($p) => [
                'type'    => $p->payment_type,
                'amount'  => $p->amount,
                'status'  => $p->status,
                'due_at'  => $p->due_at,
            ]),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/legal/accept-offer
    //
    // User কোনো lawyer এর offer accept করবে
    // Body: offer_id
    //
    // এরপর:
    //   - request status → 'confirmed'
    //   - অন্য সব offer → 'expired'
    //   - User কে 30% payment করতে বলবে
    // ══════════════════════════════════════════════════════════════════
    public function acceptOffer(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate(['offer_id' => 'required|integer']);

        $offer = LegalRequestOffer::with('legalRequest')->find($request->offer_id);

        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'Offer not found.'], 404);
        }

        // Ownership check
        if ($offer->legalRequest->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($offer->legalRequest->status !== 'open' && $offer->legalRequest->status !== 'negotiating') {
            return response()->json(['success' => false, 'message' => 'This request is no longer open.'], 422);
        }

        if ($offer->action === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Cannot accept a rejected offer.'], 422);
        }

        DB::transaction(function () use ($offer, $user) {
            $legalRequest = $offer->legalRequest;

            // Final price নির্ধারণ
            $finalPrice = $offer->action === 'countered'
                ? $offer->offered_price
                : $legalRequest->user_budget;

            // Request update
            $legalRequest->update([
                'status'             => 'confirmed',
                'accepted_lawyer_id' => $offer->lawyer_id,
                'final_price'        => $finalPrice,
                'confirmed_at'       => now(),
            ]);

            // Accepted offer mark করো
            $offer->update([
                'user_response'  => 'user_accepted',
                'responded_at'   => now(),
            ]);

            // বাকি সব offer expire করো
            LegalRequestOffer::where('legal_request_id', $legalRequest->id)
                ->where('id', '!=', $offer->id)
                ->update(['user_response' => 'expired']);

            // Accepted lawyer কে notification পাঠাও
            $this->notifyLawyer($offer->lawyer_id, [
                'title' => '🎉 Your offer was accepted!',
                'body'  => 'Case ' . $legalRequest->request_code . ' — ' . $legalRequest->case_type . '. Client will pay the advance shortly.',
                'data'  => ['type' => 'offer_accepted', 'request_id' => $legalRequest->id],
            ]);
        });

        $legalRequest = LegalRequest::find($offer->legal_request_id);
        $advanceAmount = $legalRequest->advanceAmount();

        return response()->json([
            'success'        => true,
            'message'        => 'Offer accepted! Please pay the 30% advance to start the case.',
            'legal_request'  => $this->formatRequest($legalRequest->fresh()),
            'payment_info'   => [
                'type'         => 'advance',
                'percentage'   => 30,
                'amount'       => $advanceAmount,
                'total_price'  => $legalRequest->final_price,
                'description'  => 'Pay ৳' . number_format($advanceAmount, 2) . ' advance (30% of ৳' . number_format($legalRequest->final_price, 2) . ') to start your case.',
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/legal/payment
    //
    // User payment submit করবে (30% advance অথবা 70% completion)
    // Body: legal_request_id, payment_type (advance|completion),
    //       payment_method, sender_number, txn_id
    // ══════════════════════════════════════════════════════════════════
    public function submitPayment(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'legal_request_id' => 'required|integer',
            'payment_type'     => 'required|in:advance,completion',
            'payment_method'   => 'required|in:bkash,nagad,rocket,bank',
            'sender_number'    => 'required|string|max:20',
            'txn_id'           => 'required|string|max:100|unique:legal_payments,txn_id',
        ]);

        $legalRequest = LegalRequest::where('id', $request->legal_request_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        // Status validation
        if ($request->payment_type === 'advance' && $legalRequest->status !== 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Case must be confirmed before advance payment.'], 422);
        }

        if ($request->payment_type === 'completion' && $legalRequest->status !== 'solved') {
            return response()->json(['success' => false, 'message' => 'Lawyer must mark the case as solved before completion payment.'], 422);
        }

        // Duplicate check
        $existing = LegalPayment::where('legal_request_id', $legalRequest->id)
            ->where('payment_type', $request->payment_type)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'A ' . $request->payment_type . ' payment is already submitted or confirmed.'], 422);
        }

        // Amount calculate
        $percentage = $request->payment_type === 'advance' ? 30 : 70;
        $amount     = round($legalRequest->final_price * ($percentage / 100), 2);
        $dueAt      = $request->payment_type === 'completion'
            ? $legalRequest->solved_at->addDays(5)
            : null;

        LegalPayment::create([
            'legal_request_id' => $legalRequest->id,
            'user_id'          => $user->id,
            'lawyer_id'        => $legalRequest->accepted_lawyer_id,
            'payment_type'     => $request->payment_type,
            'total_price'      => $legalRequest->final_price,
            'amount'           => $amount,
            'percentage'       => $percentage,
            'payment_method'   => $request->payment_method,
            'sender_number'    => $request->sender_number,
            'txn_id'           => $request->txn_id,
            'status'           => 'pending',
            'due_at'           => $dueAt,
        ]);

        $typeLabel = $request->payment_type === 'advance' ? '30% Advance' : '70% Completion';

        return response()->json([
            'success' => true,
            'message' => $typeLabel . ' payment submitted! Admin will confirm your transaction within a few hours.',
            'amount'  => $amount,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function getAuthUser(Request $request): ?User
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return null;
        return User::where('id', $userId)->where('status', 'Active')->first();
    }

    private function notifyAllLawyers(LegalRequest $legalRequest): void
    {
        $lawyers = Lawyer::where('status', 'approved')
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($lawyers as $lawyer) {
            $this->sendFcmNotification($lawyer->fcm_token, [
                'title' => '⚖️ New Legal Request',
                'body'  => $legalRequest->case_type . ' — Budget: ৳' . number_format($legalRequest->user_budget, 0),
                'data'  => [
                    'type'       => 'new_legal_request',
                    'request_id' => (string) $legalRequest->id,
                ],
            ]);
        }
    }

    private function notifyLawyer(int $lawyerId, array $payload): void
    {
        $lawyer = Lawyer::find($lawyerId);
        if ($lawyer && $lawyer->fcm_token) {
            $this->sendFcmNotification($lawyer->fcm_token, $payload);
        }
    }

    private function notifyUser(int $userId, array $payload): void
    {
        // তোমার existing FCM token table ব্যবহার করো
        $tokenRow = DB::table('fcm_tokens')->where('user_id', $userId)->first();
        if ($tokenRow) {
            $this->sendFcmNotification($tokenRow->token, $payload);
        }
    }

    private function sendFcmNotification(string $token, array $payload): void
    {
        // তোমার existing FCM send logic এখানে use করো
        // (SosController এ যেভাবে আছে সেভাবে)
        $serverKey = env('FCM_SERVER_KEY', '');
        if (!$serverKey || !$token) return;

        $data = [
            'to'           => $token,
            'notification' => [
                'title' => $payload['title'],
                'body'  => $payload['body'],
            ],
            'data' => $payload['data'] ?? [],
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_exec($ch);
        curl_close($ch);
    }

    private function formatRequest(LegalRequest $r): array
    {
        return [
            'id'                 => $r->id,
            'request_code'       => $r->request_code,
            'case_type'          => $r->case_type,
            'description'        => $r->description,
            'documents'          => $r->documents ?? [],
            'user_budget'        => $r->user_budget,
            'deadline_hours'     => $r->deadline_hours,
            'status'             => $r->status,
            'final_price'        => $r->final_price,
            'advance_amount'     => $r->final_price ? $r->advanceAmount() : null,
            'completion_amount'  => $r->final_price ? $r->completionAmount() : null,
            'accepted_lawyer'    => $r->acceptedLawyer ? [
                'id'           => $r->acceptedLawyer->id,
                'public_name'  => $r->acceptedLawyer->display_name ?? $r->acceptedLawyer->name_from_id,
                'specialization' => $r->acceptedLawyer->specialization,
                'rating'       => $r->acceptedLawyer->rating,
            ] : null,
            'offer_count'        => $r->offers ? $r->offers->where('action', '!=', 'rejected')->count() : 0,
            'confirmed_at'       => $r->confirmed_at,
            'started_at'         => $r->started_at,
            'solved_at'          => $r->solved_at,
            'completed_at'       => $r->completed_at,
            'created_at'         => $r->created_at,
            // Completion payment deadline (solved হলে +5 days)
            'completion_due_at'  => $r->solved_at ? $r->solved_at->addDays(5) : null,
        ];
    }

    private function formatOffer(LegalRequestOffer $o): array
    {
        return [
            'id'             => $o->id,
            'action'         => $o->action,
            'offered_price'  => $o->action === 'countered' ? $o->offered_price : null,
            'effective_price'=> $o->effectivePrice(),
            'message'        => $o->message,
            'user_response'  => $o->user_response,
            'lawyer'         => [
                'id'             => $o->lawyer->id,
                'public_name'    => $o->lawyer->display_name ?? $o->lawyer->name_from_id,
                'specialization' => $o->lawyer->specialization,
                'experience_years' => $o->lawyer->experience_years,
                'rating'         => $o->lawyer->rating,
                'total_cases'    => $o->lawyer->total_cases,
                'profile_photo'  => $o->lawyer->profile_photo,
            ],
            'created_at'     => $o->created_at,
        ];
    }
}
