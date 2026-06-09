<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LegalRequest;
use App\Models\LawyerBid;
use App\Models\LawyerNotification;
use App\Models\CasePayment;
use Laravel\Sanctum\PersonalAccessToken;

class CasePaymentController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // STEP 1 — Lawyer: "Case Resolved"
    // POST /api/case-payment/{requestId}/resolve
    // ══════════════════════════════════════════════════════════════
    public function resolveCase(Request $request, string $requestId)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $legalRequest = LegalRequest::where('request_id', $requestId)
            ->where('assigned_lawyer_id', $lawyer->id)
            ->whereIn('status', ['accepted', 'in_progress'])
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found or already resolved.'], 404);
        }

        // Duplicate check
        $existing = CasePayment::where('legal_request_id', $legalRequest->id)->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Payment request already sent for this case.'], 422);
        }

        // Accepted bid থেকে fee নাও
        $bid = LawyerBid::where('legal_request_id', $legalRequest->id)
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'accepted')
            ->first();

        if (!$bid) {
            return response()->json(['success' => false, 'message' => 'Accepted bid not found.'], 404);
        }

        $grossAmount = (float) $bid->proposed_fee;
        $commission  = (float) ($bid->platform_commission ?? round($grossAmount * 0.02, 2));
        $netAmount   = $grossAmount - $commission;
        $deadline    = now()->addDays(3);

        DB::transaction(function () use ($legalRequest, $lawyer, $bid, $grossAmount, $commission, $netAmount, $deadline) {

            // ① Payment record তৈরি করো
            CasePayment::create([
                'payment_code'     => CasePayment::generateCode(),
                'legal_request_id' => $legalRequest->id,
                'lawyer_id'        => $lawyer->id,
                'user_id'          => $legalRequest->user_id,
                'gross_amount'     => $grossAmount,
                'commission'       => $commission,
                'net_amount'       => $netAmount,
                'status'           => 'pending',
                'payment_deadline' => $deadline,
            ]);

            // ② Case status update
            $legalRequest->update([
                'status'     => 'resolved_pending_payment',
                'updated_at' => now(),
            ]);

            // ③ User কে payment notification পাঠাও
            if ($legalRequest->user_id) {
                DB::table('user_notifications')->insert([
                    'user_id'    => $legalRequest->user_id,
                    'type'       => 'payment_due',
                    'title'      => '💳 Payment Due — Case Resolved',
                    'message'    => "Your " . ucfirst($legalRequest->issue_type) . " case has been resolved by {$lawyer->full_name}. "
                                  . "Please pay ৳" . number_format($grossAmount, 0) . " by " . $deadline->format('d M Y, h:i A') . ". "
                                  . "Failure to pay within 3 days may result in legal action against you.",
                    'icon'       => '💳',
                    'is_read'    => false,
                    'created_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success'         => true,
            'message'         => 'Case marked as resolved. Client has been notified to pay within 3 days.',
            'payment_deadline'=> $deadline->toIso8601String(),
            'amount'          => $grossAmount,
            'commission'      => $commission,
            'net_amount'      => $netAmount,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // STEP 2 — User: "আমি পেমেন্ট করেছি"
    // POST /api/case-payment/{requestId}/confirm-paid
    // ══════════════════════════════════════════════════════════════
    public function confirmPaid(Request $request, string $requestId)
    {
        $userId = $this->getUserId($request);
        if (!$userId) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $legalRequest = LegalRequest::where('request_id', $requestId)
            ->where('user_id', $userId)
            ->where('status', 'resolved_pending_payment')
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found or payment not due.'], 404);
        }

        $payment = CasePayment::where('legal_request_id', $legalRequest->id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No pending payment found for this case.'], 404);
        }

        $confirmDeadline = now()->addHours(48);

        DB::transaction(function () use ($payment, $legalRequest, $confirmDeadline) {

            // ① Payment status → claimed
            $payment->update([
                'status'          => 'claimed',
                'paid_claimed_at' => now(),
                'claim_deadline'  => $confirmDeadline,
            ]);

            // ② Lawyer কে confirmation request notification পাঠাও
            LawyerNotification::create([
                'lawyer_id'  => $payment->lawyer_id,
                'type'       => 'payment_claimed',
                'title'      => '💰 Payment Confirmation Required',
                'body'       => "Your client says they have paid ৳" . number_format($payment->gross_amount, 0)
                              . " for the " . ucfirst($legalRequest->issue_type) . " case. "
                              . "Did you receive this payment? Please respond within 48 hours.",
                'data'       => json_encode([
                    'request_id'  => $legalRequest->request_id,
                    'payment_id'  => $payment->id,
                    'amount'      => $payment->gross_amount,
                    'action_type' => 'payment_confirmation',  // frontend এ Yes/No button দেখাবে
                ]),
                'is_read'    => false,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment claim sent to your lawyer. They will confirm within 48 hours.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // STEP 3 — Lawyer: "Yes পেয়েছি" বা "No পাইনি"
    // POST /api/case-payment/{requestId}/payment-response
    // Body: { "received": true/false }
    // ══════════════════════════════════════════════════════════════
    public function paymentResponse(Request $request, string $requestId)
    {
        $request->validate([
            'received' => 'required|boolean',
        ]);

        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $legalRequest = LegalRequest::where('request_id', $requestId)
            ->where('assigned_lawyer_id', $lawyer->id)
            ->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $payment = CasePayment::where('legal_request_id', $legalRequest->id)
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'claimed')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No claimed payment found for this case.'], 404);
        }

        if ($request->boolean('received')) {
            // ──────────────────────────────────────────────────────
            // ✅ LAWYER CONFIRMED: পেয়েছি — earnings update
            // ──────────────────────────────────────────────────────
            DB::transaction(function () use ($payment, $legalRequest, $lawyer) {

                // ① Payment confirmed
                $payment->update([
                    'status'       => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                // ② Lawyer earnings বাড়াও (net amount — commission বাদে)
                Lawyer::where('id', $lawyer->id)->increment('total_earned', $payment->net_amount);
                Lawyer::where('id', $lawyer->id)->increment('total_commission_paid', $payment->commission);
                Lawyer::where('id', $lawyer->id)->increment('completed_cases');

                // ③ Case closed
                $legalRequest->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'updated_at'   => now(),
                ]);

                // ④ User কে confirmation notification
                if ($legalRequest->user_id) {
                    DB::table('user_notifications')->insert([
                        'user_id'    => $legalRequest->user_id,
                        'type'       => 'payment_confirmed',
                        'title'      => '✅ Payment Confirmed',
                        'message'    => "Your payment of ৳" . number_format($payment->gross_amount, 0)
                                      . " for the " . ucfirst($legalRequest->issue_type)
                                      . " case has been confirmed by {$lawyer->full_name}. Your case is now officially closed. Thank you for using SafeVoice.",
                        'icon'       => '✅',
                        'is_read'    => false,
                        'created_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'success'    => true,
                'message'    => '✅ Payment confirmed! Your earnings have been updated.',
                'net_earned' => $payment->net_amount,
                'commission' => $payment->commission,
                'gross'      => $payment->gross_amount,
            ]);

        } else {
            // ──────────────────────────────────────────────────────
            // ❌ LAWYER DENIED: পাইনি — user কে warn করো
            // ──────────────────────────────────────────────────────
            DB::transaction(function () use ($payment, $legalRequest, $lawyer) {

                // ① Payment disputed
                $payment->update([
                    'status'       => 'disputed',
                    'disputed_at'  => now(),
                ]);

                // ② Case status → payment_disputed
                $legalRequest->update([
                    'status'     => 'payment_disputed',
                    'updated_at' => now(),
                ]);

                // ③ User কে stern warning পাঠাও
                if ($legalRequest->user_id) {
                    DB::table('user_notifications')->insert([
                        'user_id'    => $legalRequest->user_id,
                        'type'       => 'payment_disputed',
                        'title'      => '⚠️ Payment Not Received — Action Required',
                        'message'    => "{$lawyer->full_name} has not received your payment of ৳"
                                      . number_format($payment->gross_amount, 0)
                                      . " for the " . ucfirst($legalRequest->issue_type) . " case. "
                                      . "If you have already paid and have proof (bank receipt, screenshot), "
                                      . "please email our admin at admin@safevoice.com within 48 hours to stop action against you. "
                                      . "After 48 hours, legal action will be taken against your account.",
                        'icon'       => '⚠️',
                        'is_read'    => false,
                        'created_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Dispute recorded. The client has been warned and given 48 hours to provide proof.',
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // GET /api/case-payment/{requestId}/status
    // Payment এর current status দেখা (user বা lawyer যে কেউ)
    // ══════════════════════════════════════════════════════════════
    public function paymentStatus(Request $request, string $requestId)
    {
        $legalRequest = LegalRequest::where('request_id', $requestId)->first();
        if (!$legalRequest) return response()->json(['success' => false, 'message' => 'Case not found.'], 404);

        $payment = CasePayment::where('legal_request_id', $legalRequest->id)->first();
        if (!$payment) return response()->json(['success' => false, 'message' => 'No payment record found.'], 404);

        return response()->json([
            'success' => true,
            'payment' => [
                'payment_code'     => $payment->payment_code,
                'status'           => $payment->status,
                'gross_amount'     => $payment->gross_amount,
                'commission'       => $payment->commission,
                'net_amount'       => $payment->net_amount,
                'payment_deadline' => $payment->payment_deadline->toIso8601String(),
                'days_left'        => $payment->daysLeft(),
                'is_overdue'       => $payment->isOverdue(),
                'paid_claimed_at'  => $payment->paid_claimed_at?->toIso8601String(),
                'claim_deadline'   => $payment->claim_deadline?->toIso8601String(),
                'hours_for_lawyer' => $payment->hoursLeftForLawyerConfirm(),
                'confirmed_at'     => $payment->confirmed_at?->toIso8601String(),
                'disputed_at'      => $payment->disputed_at?->toIso8601String(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /api/lawyer/earnings
    // Lawyer এর total earnings summary
    // ══════════════════════════════════════════════════════════════
    public function earnings(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        $payments = CasePayment::where('lawyer_id', $lawyer->id)
            ->with('legalRequest:id,request_id,issue_type,user_name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'payment_code'   => $p->payment_code,
                'request_id'     => $p->legalRequest?->request_id,
                'issue_type'     => $p->legalRequest?->issue_type,
                'client_name'    => $p->legalRequest?->user_name ?? 'Client',
                'gross_amount'   => $p->gross_amount,
                'commission'     => $p->commission,
                'net_amount'     => $p->net_amount,
                'status'         => $p->status,
                'payment_deadline' => $p->payment_deadline->toIso8601String(),
                'confirmed_at'   => $p->confirmed_at?->toIso8601String(),
                'created_at'     => $p->created_at,
            ]);

        $confirmed = $payments->where('status', 'confirmed');

        return response()->json([
            'success' => true,
            'summary' => [
                'total_earned'         => $lawyer->total_earned,
                'total_commission_paid'=> $lawyer->total_commission_paid,
                'confirmed_payments'   => $confirmed->count(),
                'pending_payments'     => $payments->whereIn('status', ['pending', 'claimed'])->count(),
                'disputed_payments'    => $payments->where('status', 'disputed')->count(),
            ],
            'payments' => $payments,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // LAWYER: Pending payment কে dispute করো
    // POST /api/case-payment/{requestId}/dispute-pending
    // ══════════════════════════════════════════════════════════════
    public function disputePending(Request $request, string $requestId)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $legalRequest = LegalRequest::where('request_id', $requestId)->first();

        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $payment = CasePayment::where('legal_request_id', $legalRequest->id)
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No pending payment found.'], 404);
        }

        // Check deadline passed (lawyer can dispute if client hasn't paid in time)
        DB::transaction(function () use ($payment, $legalRequest, $lawyer) {
            $payment->update([
                'status'      => 'disputed',
                'disputed_at' => now(),
            ]);

            $legalRequest->update([
                'status'     => 'payment_disputed',
                'updated_at' => now(),
            ]);

            // User কে warn করো
            if ($legalRequest->user_id) {
                DB::table('user_notifications')->insert([
                    'user_id'    => $legalRequest->user_id,
                    'type'       => 'payment_disputed',
                    'title'      => '⚠️ Payment Overdue — Action Required',
                    'message'    => "{$lawyer->full_name} has flagged your payment of ৳"
                                  . number_format($payment->gross_amount, 0)
                                  . " for the " . ucfirst($legalRequest->issue_type) . " case as overdue. "
                                  . "Please pay immediately or contact admin@safevoice.com within 48 hours to avoid legal action.",
                    'icon'       => '⚠️',
                    'is_read'    => false,
                    'created_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment flagged as disputed. Client has been warned.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // LAWYER: Disputed payment এ admin contact notification পাঠাও
    // POST /api/case-payment/{requestId}/contact-admin
    // ══════════════════════════════════════════════════════════════
    public function contactAdmin(Request $request, string $requestId)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $legalRequest = LegalRequest::where('request_id', $requestId)->first();
        if (!$legalRequest) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $payment = CasePayment::where('legal_request_id', $legalRequest->id)
            ->where('lawyer_id', $lawyer->id)
            ->whereIn('status', ['disputed'])
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No disputed payment found.'], 404);
        }

        // Throttle: ২৪ ঘণ্টায় ১ বারের বেশি contact করতে পারবে না
        if ($payment->admin_contacted_at && now()->diffInHours($payment->admin_contacted_at) < 24) {
            return response()->json([
                'success' => false,
                'message' => 'You already contacted admin recently. Please wait 24 hours before contacting again.',
            ], 429);
        }

        $payment->update(['admin_contacted_at' => now()]);

        // Admin কে notification — admin_notifications বা user_notifications (admin user_id = 1)
        DB::table('user_notifications')->insert([
            'user_id'    => 1, // Admin
            'type'       => 'lawyer_admin_contact',
            'title'      => '🚨 Lawyer Contacted Admin — Payment Dispute',
            'message'    => "Lawyer {$lawyer->full_name} ({$lawyer->lawyer_code}) has requested admin intervention "
                          . "for disputed payment of ৳" . number_format($payment->gross_amount, 0)
                          . " on case {$legalRequest->request_id} ({$legalRequest->issue_type}). "
                          . "Client: {$legalRequest->user_name}. Please review and resolve.",
            'icon'       => '🚨',
            'is_read'    => false,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin has been notified. They will review your case within 24 hours. You can also email admin@safevoice.com directly.',
        ]);
    }

    // ── Auth Helpers ───────────────────────────────────────────────
    private function getAuthLawyer(Request $request): ?Lawyer
    {
        $id = $request->session()->get('lawyer_id');
        if ($id) return Lawyer::find($id);

        $token = $request->bearerToken();
        if ($token) {
            try {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable_type === Lawyer::class) {
                    return Lawyer::find($pat->tokenable_id);
                }
            } catch (\Exception $e) {}
        }

        $qid = $request->query('lawyer_id') ?? $request->input('lawyer_id');
        return $qid ? Lawyer::find($qid) : null;
    }

    private function getUserId(Request $request): ?int
    {
        $token = $request->bearerToken();
        if ($token) {
            try {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable_type === \App\Models\User::class) {
                    return (int) $pat->tokenable_id;
                }
            } catch (\Exception $e) {}
        }

        $id = $request->session()->get('user_id') ?? $request->query('user_id');
        return $id ? (int) $id : null;
    }
}