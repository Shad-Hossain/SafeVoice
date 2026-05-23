<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LegalPayment;

class AdminLegalController extends Controller
{
    // Admin auth check helper (তোমার existing AdminController এর pattern)
    private function isAdmin(Request $request): bool
    {
        return $request->session()->get('is_admin') === true;
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/admin/lawyers/pending
    //
    // Pending lawyer accounts — admin দেখে verify করবে
    // ══════════════════════════════════════════════════════════════════
    public function pendingLawyers(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $lawyers = Lawyer::where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->map(fn($l) => [
                'id'              => $l->id,
                'bar_council_id'  => $l->bar_council_id,
                'name_from_id'    => $l->name_from_id,
                'email'           => $l->email,
                'phone'           => $l->phone,
                'specialization'  => $l->specialization,
                'id_card_path'    => $l->id_card_path,       // Admin এই image দেখে verify করবে
                'id_card_url'     => url($l->id_card_path),  // Full URL
                'is_ocr_pending'  => str_starts_with($l->bar_council_id, 'PENDING_OCR_'), // OCR fail হয়েছিল
                'created_at'      => $l->created_at,
            ]);

        return response()->json([
            'success' => true,
            'count'   => $lawyers->count(),
            'lawyers' => $lawyers,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/admin/lawyers
    //
    // সব lawyers (সব status)
    // ══════════════════════════════════════════════════════════════════
    public function allLawyers(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $status  = $request->status; // optional filter
        $query   = Lawyer::orderByDesc('created_at');
        if ($status) $query->where('status', $status);

        $lawyers = $query->get()->map(fn($l) => [
            'id'             => $l->id,
            'bar_council_id' => $l->bar_council_id,
            'name_from_id'   => $l->name_from_id,
            'display_name'   => $l->display_name,
            'email'          => $l->email,
            'phone'          => $l->phone,
            'specialization' => $l->specialization,
            'status'         => $l->status,
            'total_cases'    => $l->total_cases,
            'active_cases'   => $l->active_cases,
            'rating'         => $l->rating,
            'approved_at'    => $l->approved_at,
            'created_at'     => $l->created_at,
        ]);

        return response()->json(['success' => true, 'lawyers' => $lawyers]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/admin/lawyers/approve
    //
    // Admin lawyer approve করবে
    // Body: lawyer_id, bar_council_id (optional correction), name (optional correction)
    //
    // Admin চাইলে OCR extracted ID/Name correct করে দিতে পারবে
    // ══════════════════════════════════════════════════════════════════
    public function approveLawyer(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate([
            'lawyer_id'      => 'required|integer',
            'bar_council_id' => 'nullable|string|max:100', // admin correction
            'name_from_id'   => 'nullable|string|max:150', // admin correction
        ]);

        $lawyer = Lawyer::find($request->lawyer_id);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Lawyer not found.'], 404);
        }

        if ($lawyer->status === 'approved') {
            return response()->json(['success' => false, 'message' => 'Already approved.'], 422);
        }

        $updateData = [
            'status'      => 'approved',
            'admin_note'  => null,
            'approved_at' => now(),
        ];

        // Admin OCR correction করলে update করো
        if ($request->bar_council_id) {
            $updateData['bar_council_id'] = $request->bar_council_id;
        }
        if ($request->name_from_id) {
            $updateData['name_from_id'] = $request->name_from_id;
        }

        $lawyer->update($updateData);

        // Lawyer কে notification পাঠাও
        if ($lawyer->fcm_token) {
            $this->sendFcm($lawyer->fcm_token, [
                'title' => '✅ Account Approved!',
                'body'  => 'Your Bar Council ID has been verified. You can now login and start accepting cases.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lawyer account approved. They can now login.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/admin/lawyers/reject
    //
    // Admin lawyer reject করবে
    // Body: lawyer_id, reason
    // ══════════════════════════════════════════════════════════════════
    public function rejectLawyer(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate([
            'lawyer_id' => 'required|integer',
            'reason'    => 'required|string|max:500',
        ]);

        $lawyer = Lawyer::find($request->lawyer_id);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Lawyer not found.'], 404);
        }

        $lawyer->update([
            'status'     => 'rejected',
            'admin_note' => $request->reason,
        ]);

        // Lawyer কে notify করো
        if ($lawyer->fcm_token) {
            $this->sendFcm($lawyer->fcm_token, [
                'title' => '❌ Registration Rejected',
                'body'  => 'Your registration was rejected. Reason: ' . $request->reason,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lawyer account rejected.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/admin/lawyers/update-status
    //
    // Admin lawyer suspend / reactivate করবে
    // Body: lawyer_id, status (approved|suspended)
    // ══════════════════════════════════════════════════════════════════
    public function updateLawyerStatus(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate([
            'lawyer_id' => 'required|integer',
            'status'    => 'required|in:approved,suspended',
        ]);

        $lawyer = Lawyer::find($request->lawyer_id);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Lawyer not found.'], 404);
        }

        $lawyer->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Lawyer status updated to ' . $request->status,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/admin/legal/payments/pending
    //
    // Pending payments (advance + completion) — admin verify করবে
    // ══════════════════════════════════════════════════════════════════
    public function pendingPayments(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $payments = LegalPayment::where('status', 'pending')
            ->with([
                'legalRequest:id,request_code,case_type,status',
                'legalRequest.user:id,name,phone',
                'legalRequest.acceptedLawyer:id,name_from_id,display_name',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'payment_type'   => $p->payment_type,
                'percentage'     => $p->percentage . '%',
                'amount'         => $p->amount,
                'total_price'    => $p->total_price,
                'payment_method' => $p->payment_method,
                'sender_number'  => $p->sender_number,
                'txn_id'         => $p->txn_id,
                'due_at'         => $p->due_at,
                'request_code'   => $p->legalRequest->request_code,
                'case_type'      => $p->legalRequest->case_type,
                'user_name'      => $p->legalRequest->user->name ?? '—',
                'user_phone'     => $p->legalRequest->user->phone ?? '—',
                'lawyer_name'    => $p->legalRequest->acceptedLawyer
                    ? ($p->legalRequest->acceptedLawyer->display_name ?? $p->legalRequest->acceptedLawyer->name_from_id)
                    : '—',
                'submitted_at'   => $p->created_at,
            ]);

        return response()->json([
            'success'  => true,
            'count'    => $payments->count(),
            'payments' => $payments,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/admin/legal/payments/confirm
    //
    // Admin payment confirm করবে (txn_id verify করে)
    // Body: payment_id
    //
    // Advance confirm হলে:  case status → 'ongoing', started_at set
    // Completion confirm হলে: case status → 'completed', completed_at set
    // ══════════════════════════════════════════════════════════════════
    public function confirmPayment(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate(['payment_id' => 'required|integer']);

        $payment = LegalPayment::with('legalRequest')->find($request->payment_id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Payment already processed.'], 422);
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'       => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $legalRequest = $payment->legalRequest;

            if ($payment->payment_type === 'advance') {
                // 30% advance confirmed → case শুরু হলো
                $legalRequest->update([
                    'status'     => 'ongoing',
                    'started_at' => now(),
                ]);

                // Lawyer কে notify করো
                $this->sendFcm(
                    $legalRequest->acceptedLawyer->fcm_token ?? null,
                    [
                        'title' => '💰 Advance Payment Confirmed!',
                        'body'  => 'Client has paid 30% advance for case ' . $legalRequest->request_code . '. Case is now ongoing.',
                        'data'  => ['type' => 'advance_confirmed', 'request_id' => (string) $legalRequest->id],
                    ]
                );

                // User কে notify করো
                $userToken = DB::table('fcm_tokens')->where('user_id', $legalRequest->user_id)->value('token');
                $this->sendFcm($userToken, [
                    'title' => '✅ Payment Confirmed — Case Started!',
                    'body'  => 'Your 30% advance payment is confirmed. Your lawyer will now begin working on your case.',
                    'data'  => ['type' => 'advance_confirmed', 'request_id' => (string) $legalRequest->id],
                ]);

                // Lawyer stats update
                $legalRequest->acceptedLawyer->increment('active_cases');
                $legalRequest->acceptedLawyer->increment('total_cases');

            } elseif ($payment->payment_type === 'completion') {
                // 70% completion confirmed → case সম্পূর্ণ শেষ
                $legalRequest->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);

                // Lawyer এর active_cases কমাও
                $legalRequest->acceptedLawyer?->decrement('active_cases');

                // Lawyer notify
                $this->sendFcm(
                    $legalRequest->acceptedLawyer->fcm_token ?? null,
                    [
                        'title' => '🎉 Case Completed!',
                        'body'  => 'Full payment received for case ' . $legalRequest->request_code . '. Case is now closed.',
                        'data'  => ['type' => 'case_completed', 'request_id' => (string) $legalRequest->id],
                    ]
                );

                // User notify
                $userToken = DB::table('fcm_tokens')->where('user_id', $legalRequest->user_id)->value('token');
                $this->sendFcm($userToken, [
                    'title' => '✅ Case Completed!',
                    'body'  => 'Your legal case ' . $legalRequest->request_code . ' has been successfully closed. Thank you!',
                    'data'  => ['type' => 'case_completed', 'request_id' => (string) $legalRequest->id],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => ucfirst($payment->payment_type) . ' payment confirmed. Case status updated.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/admin/legal/payments/reject
    //
    // Admin payment reject করবে (invalid txn)
    // Body: payment_id, reason
    // ══════════════════════════════════════════════════════════════════
    public function rejectPayment(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate([
            'payment_id' => 'required|integer',
            'reason'     => 'nullable|string|max:300',
        ]);

        $payment = LegalPayment::find($request->payment_id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        $payment->update(['status' => 'failed']);

        return response()->json(['success' => true, 'message' => 'Payment rejected.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/admin/legal/requests
    //
    // সব legal requests overview (admin)
    // ══════════════════════════════════════════════════════════════════
    public function allRequests(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $status = $request->status;
        $query  = LegalRequest::with(['user:id,name,phone', 'acceptedLawyer:id,name_from_id,display_name'])
            ->orderByDesc('created_at');

        if ($status) $query->where('status', $status);

        $requests = $query->paginate(20)->through(fn($r) => [
            'id'             => $r->id,
            'request_code'   => $r->request_code,
            'case_type'      => $r->case_type,
            'status'         => $r->status,
            'user_budget'    => $r->user_budget,
            'final_price'    => $r->final_price,
            'user_name'      => $r->user->name ?? '—',
            'lawyer_name'    => $r->acceptedLawyer
                ? ($r->acceptedLawyer->display_name ?? $r->acceptedLawyer->name_from_id)
                : '—',
            'created_at'     => $r->created_at,
            'started_at'     => $r->started_at,
            'solved_at'      => $r->solved_at,
            'completed_at'   => $r->completed_at,
        ]);

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ── FCM helper ────────────────────────────────────────────────────
    private function sendFcm(?string $token, array $payload): void
    {
        if (!$token) return;
        $serverKey = env('FCM_SERVER_KEY', '');
        if (!$serverKey) return;

        $data = [
            'to'           => $token,
            'notification' => ['title' => $payload['title'], 'body' => $payload['body']],
            'data'         => $payload['data'] ?? [],
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
}
