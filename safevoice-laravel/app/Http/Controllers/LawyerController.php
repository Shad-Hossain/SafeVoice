<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LegalRequestOffer;
use App\Models\LegalCaseUpdate;

class LawyerController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // GET /api/lawyer/open-requests
    //
    // Approved lawyer সব open legal requests দেখবে
    // Filter: case_type, min_budget, max_budget
    // ══════════════════════════════════════════════════════════════════
    public function openRequests(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $query = LegalRequest::whereIn('status', ['open', 'negotiating'])
            ->with(['user:id,name,phone', 'offers' => fn($q) => $q->where('lawyer_id', $lawyer->id)])
            ->orderByDesc('created_at');

        // Optional filters
        if ($request->case_type) {
            $query->where('case_type', $request->case_type);
        }
        if ($request->min_budget) {
            $query->where('user_budget', '>=', $request->min_budget);
        }
        if ($request->max_budget) {
            $query->where('user_budget', '<=', $request->max_budget);
        }

        $requests = $query->get()->map(function ($r) use ($lawyer) {
            $myOffer = $r->offers->first(); // এই lawyer এর offer (যদি থাকে)
            return [
                'id'             => $r->id,
                'request_code'   => $r->request_code,
                'case_type'      => $r->case_type,
                'description'    => substr($r->description, 0, 200) . (strlen($r->description) > 200 ? '...' : ''),
                'has_documents'  => !empty($r->documents),
                'document_count' => count($r->documents ?? []),
                'user_budget'    => $r->user_budget,
                'deadline_hours' => $r->deadline_hours,
                'status'         => $r->status,
                'my_offer'       => $myOffer ? [
                    'action'        => $myOffer->action,
                    'offered_price' => $myOffer->offered_price,
                    'user_response' => $myOffer->user_response,
                ] : null,
                'already_offered'=> !is_null($myOffer),
                'created_at'     => $r->created_at,
                'expires_at'     => $r->created_at->addHours($r->deadline_hours),
            ];
        });

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/lawyer/request/{id}
    //
    // Single request details — full description + documents
    // ══════════════════════════════════════════════════════════════════
    public function showRequest(Request $request, int $id)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $legalRequest = LegalRequest::whereIn('status', ['open', 'negotiating'])
            ->with('user:id,name,phone')
            ->find($id);

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found or no longer available.'], 404);
        }

        $myOffer = LegalRequestOffer::where('legal_request_id', $id)
            ->where('lawyer_id', $lawyer->id)
            ->first();

        return response()->json([
            'success'        => true,
            'legal_request'  => [
                'id'             => $legalRequest->id,
                'request_code'   => $legalRequest->request_code,
                'case_type'      => $legalRequest->case_type,
                'description'    => $legalRequest->description,  // full description
                'documents'      => $legalRequest->documents ?? [],
                'user_budget'    => $legalRequest->user_budget,
                'deadline_hours' => $legalRequest->deadline_hours,
                'status'         => $legalRequest->status,
                'created_at'     => $legalRequest->created_at,
                'expires_at'     => $legalRequest->created_at->addHours($legalRequest->deadline_hours),
            ],
            'my_offer' => $myOffer,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/offer
    //
    // Lawyer request এ respond করবে: accept / counter / reject
    // Body: legal_request_id, action, offered_price (counter হলে), message
    // ══════════════════════════════════════════════════════════════════
    public function makeOffer(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'legal_request_id' => 'required|integer',
            'action'           => 'required|in:accepted,countered,rejected',
            'offered_price'    => 'required_if:action,countered|nullable|numeric|min:100',
            'message'          => 'nullable|string|max:500',
        ]);

        $legalRequest = LegalRequest::find($request->legal_request_id);

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        if (!in_array($legalRequest->status, ['open', 'negotiating'])) {
            return response()->json(['success' => false, 'message' => 'This request is no longer accepting offers.'], 422);
        }

        // একজন lawyer একটা request এ একবারই offer করতে পারবে
        $existing = LegalRequestOffer::where('legal_request_id', $request->legal_request_id)
            ->where('lawyer_id', $lawyer->id)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You have already responded to this request.'], 422);
        }

        LegalRequestOffer::create([
            'legal_request_id' => $request->legal_request_id,
            'lawyer_id'        => $lawyer->id,
            'action'           => $request->action,
            'offered_price'    => $request->action === 'countered' ? $request->offered_price : null,
            'message'          => $request->message,
            'user_response'    => 'pending',
        ]);

        // Request status → negotiating (open থেকে)
        if ($legalRequest->status === 'open' && $request->action !== 'rejected') {
            $legalRequest->update(['status' => 'negotiating']);
        }

        // User কে notification পাঠাও (reject বাদে)
        if ($request->action !== 'rejected') {
            $actionText = $request->action === 'accepted'
                ? 'accepted your budget'
                : 'sent a counter offer of ৳' . number_format($request->offered_price, 0);

            $this->notifyUser($legalRequest->user_id, [
                'title' => '⚖️ New offer on your legal request',
                'body'  => ($lawyer->display_name ?? $lawyer->name_from_id) . ' ' . $actionText,
                'data'  => [
                    'type'       => 'legal_offer',
                    'request_id' => (string) $legalRequest->id,
                ],
            ]);
        }

        $actionMessages = [
            'accepted'  => 'You accepted the client\'s budget. Waiting for client confirmation.',
            'countered' => 'Counter offer sent. Waiting for client response.',
            'rejected'  => 'You rejected this request.',
        ];

        return response()->json([
            'success' => true,
            'message' => $actionMessages[$request->action],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/lawyer/my-cases
    //
    // Lawyer এর accepted cases দেখবে
    // ══════════════════════════════════════════════════════════════════
    public function myCases(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $cases = LegalRequest::where('accepted_lawyer_id', $lawyer->id)
            ->whereIn('status', ['confirmed', 'ongoing', 'solved', 'completed'])
            ->with(['user:id,name,phone,email', 'payments'])
            ->orderByDesc('confirmed_at')
            ->get()
            ->map(fn($r) => $this->formatCaseForLawyer($r));

        return response()->json(['success' => true, 'cases' => $cases]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/case/update
    //
    // Lawyer case status update করবে এবং note যোগ করবে
    // Body: legal_request_id, note, status_change (ongoing|solved)
    //
    // status_change = 'solved' হলে:
    //   - case status → 'solved'
    //   - solved_at set হবে
    //   - User কে 70% payment করতে notification যাবে (5 দিনের deadline)
    // ══════════════════════════════════════════════════════════════════
    public function updateCaseStatus(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'legal_request_id' => 'required|integer',
            'note'             => 'required|string|min:5|max:1000',
            'status_change'    => 'nullable|in:ongoing,solved',
        ]);

        $legalRequest = LegalRequest::where('id', $request->legal_request_id)
            ->where('accepted_lawyer_id', $lawyer->id)
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        // Ongoing বা Solved এ থাকলেই update করতে পারবে
        if (!in_array($legalRequest->status, ['ongoing', 'solved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Case updates can only be added when case is ongoing. (Has the client paid the 30% advance?)',
            ], 422);
        }

        // 'solved' আগেই mark করা থাকলে আবার solved করা যাবে না
        if ($request->status_change === 'solved' && $legalRequest->status === 'solved') {
            return response()->json(['success' => false, 'message' => 'Case is already marked as solved.'], 422);
        }

        DB::transaction(function () use ($request, $legalRequest, $lawyer) {
            // Case update note save করো
            LegalCaseUpdate::create([
                'legal_request_id' => $legalRequest->id,
                'lawyer_id'        => $lawyer->id,
                'note'             => $request->note,
                'status_change'    => $request->status_change,
            ]);

            // Solved mark করলে case status update করো
            if ($request->status_change === 'solved') {
                $legalRequest->update([
                    'status'    => 'solved',
                    'solved_at' => now(),
                ]);

                $completionAmount = $legalRequest->completionAmount();
                $dueDate          = now()->addDays(5)->format('d M Y');

                // User কে 70% payment এর জন্য notify করো
                $this->notifyUser($legalRequest->user_id, [
                    'title' => '✅ Your case has been resolved!',
                    'body'  => 'Please pay ৳' . number_format($completionAmount, 0) . ' (70% completion) within 5 days by ' . $dueDate . '.',
                    'data'  => [
                        'type'       => 'case_solved',
                        'request_id' => (string) $legalRequest->id,
                        'due_date'   => $dueDate,
                        'amount'     => $completionAmount,
                    ],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $request->status_change === 'solved'
                ? 'Case marked as solved. Client has been notified to pay the 70% completion fee within 5 days.'
                : 'Case update added.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/lawyer/my-offers
    //
    // Lawyer এর সব pending offers
    // ══════════════════════════════════════════════════════════════════
    public function myOffers(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $offers = LegalRequestOffer::where('lawyer_id', $lawyer->id)
            ->with('legalRequest:id,request_code,case_type,user_budget,final_price,status')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => [
                'offer_id'       => $o->id,
                'action'         => $o->action,
                'offered_price'  => $o->offered_price,
                'message'        => $o->message,
                'user_response'  => $o->user_response,
                'request_code'   => $o->legalRequest->request_code,
                'case_type'      => $o->legalRequest->case_type,
                'case_status'    => $o->legalRequest->status,
                'created_at'     => $o->created_at,
            ]);

        return response()->json(['success' => true, 'offers' => $offers]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function getAuthLawyer(Request $request): ?Lawyer
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) return null;
        return Lawyer::where('id', $lawyerId)->where('status', 'approved')->first();
    }

    private function notifyUser(int $userId, array $payload): void
    {
        $tokenRow = DB::table('fcm_tokens')->where('user_id', $userId)->first();
        if ($tokenRow) {
            $token     = $tokenRow->token;
            $serverKey = env('FCM_SERVER_KEY', '');
            if (!$serverKey) return;

            $data = [
                'to'           => $token,
                'notification' => ['title' => $payload['title'], 'body' => $payload['body']],
                'data'         => $payload['data'] ?? [],
            ];

            $ch = curl_init('https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: key=' . $serverKey, 'Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_exec($ch);
            curl_close($ch);
        }
    }

    private function formatCaseForLawyer(LegalRequest $r): array
    {
        $advancePaid     = $r->payments->where('payment_type', 'advance')->where('status', 'confirmed')->first();
        $completionPaid  = $r->payments->where('payment_type', 'completion')->where('status', 'confirmed')->first();

        return [
            'id'                  => $r->id,
            'request_code'        => $r->request_code,
            'case_type'           => $r->case_type,
            'description'         => $r->description,
            'status'              => $r->status,
            'final_price'         => $r->final_price,
            'advance_amount'      => $r->advanceAmount(),
            'completion_amount'   => $r->completionAmount(),
            'advance_paid'        => !is_null($advancePaid),
            'completion_paid'     => !is_null($completionPaid),
            'client'              => [
                'name'  => $r->user->name,
                'phone' => $r->user->phone,
                'email' => $r->user->email,
            ],
            'confirmed_at'        => $r->confirmed_at,
            'started_at'          => $r->started_at,
            'solved_at'           => $r->solved_at,
            'completed_at'        => $r->completed_at,
            'completion_due_at'   => $r->solved_at ? $r->solved_at->addDays(5) : null,
        ];
    }
}
