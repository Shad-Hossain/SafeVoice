<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use Laravel\Sanctum\PersonalAccessToken;

class CommissionController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // GET /api/lawyer/commission/summary
    // Lawyer এর commission summary — due, paid, pending
    // ══════════════════════════════════════════════════════════════
    public function summary(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        // Total commission accrued (confirmed case payments থেকে)
        $totalAccrued = DB::table('case_payments')
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'confirmed')
            ->sum('commission');

        // Total commission already approved (paid to platform)
        $totalApproved = DB::table('lawyer_commission_payments')
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'approved')
            ->sum('amount');

        // Pending approval amount
        $pendingAmount = DB::table('lawyer_commission_payments')
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'pending')
            ->sum('amount');

        // Balance = accrued - approved - pending
        // due = accrued minus only APPROVED payments
        // pending submissions do NOT reduce the due amount until admin approves
        $balance = $totalAccrued - $totalApproved - $pendingAmount;
        $due = max(0, $totalAccrued - $totalApproved);

        // Commission payment history
        $history = DB::table('lawyer_commission_payments')
            ->where('lawyer_id', $lawyer->id)
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get(['ref_code', 'amount', 'method', 'status', 'submitted_at', 'reviewed_at', 'admin_note']);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_accrued'  => (float) $totalAccrued,
                'total_paid'     => (float) $totalApproved,
                'pending_amount' => (float) $pendingAmount,
                'balance'        => (float) $balance,
                'due'            => (float) $due,
            ],
            'history' => $history,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /api/lawyer/commission/pay
    // Lawyer commission payment submit করো
    // ══════════════════════════════════════════════════════════════
    public function submitPayment(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $request->validate([
            'amount'          => 'required|numeric|min:1',
            'method'          => 'required|in:bkash,rocket,nagad,bank',
            'transaction_ref' => 'required|string|max:100',
        ]);

        // Duplicate transaction ref check
        $exists = DB::table('lawyer_commission_payments')
            ->where('transaction_ref', $request->transaction_ref)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction reference has already been submitted.',
            ], 422);
        }

        // Already has a pending payment — block until admin reviews it
        $hasPending = DB::table('lawyer_commission_payments')
            ->where('lawyer_id', $lawyer->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a commission payment pending admin approval. Please wait for it to be reviewed before submitting another.',
            ], 422);
        }

        $refCode = $this->generateRefCode();

        $payAmount = (float) $request->input('amount');
        $payMethod = $request->input('method');
        $payTxRef  = $request->input('transaction_ref');

        DB::table('lawyer_commission_payments')->insert([
            'ref_code'        => $refCode,
            'lawyer_id'       => $lawyer->id,
            'amount'          => $payAmount,
            'method'          => $payMethod,
            'transaction_ref' => $payTxRef,
            'status'          => 'pending',
            'submitted_at'    => now(),
        ]);

        // Admin কে notification পাঠাও
        DB::table('user_notifications')->insert([
            'user_id'    => 1, // Admin
            'type'       => 'commission_payment',
            'title'      => '💰 Commission Payment Received — Verify Required',
            'message'    => "Lawyer {$lawyer->full_name} ({$lawyer->lawyer_code}) submitted a commission payment of ৳"
                          . number_format($payAmount, 2)
                          . " via " . strtoupper($payMethod)
                          . ". Transaction Ref: {$payTxRef}. Ref Code: {$refCode}. Please verify and approve.",
            'icon'       => '💰',
            'is_read'    => false,
            'created_at' => now(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Commission payment submitted. Admin will verify within 24 hours.',
            'ref_code' => $refCode,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /api/admin/commission/pending
    // Admin: pending commission payments দেখো
    // ══════════════════════════════════════════════════════════════
    public function pendingPayments()
    {
        $payments = DB::table('lawyer_commission_payments as cp')
            ->join('lawyers as l', 'l.id', '=', 'cp.lawyer_id')
            ->where('cp.status', 'pending')
            ->orderBy('cp.submitted_at')
            ->get([
                'cp.id', 'cp.ref_code', 'cp.amount', 'cp.method',
                'cp.transaction_ref', 'cp.screenshot_path',
                'cp.status', 'cp.submitted_at',
                'l.id as lawyer_id', 'l.full_name', 'l.lawyer_code', 'l.email',
            ]);

        return response()->json([
            'success'  => true,
            'payments' => $payments,
            'count'    => $payments->count(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /api/admin/commission/all
    // Admin: সব commission payments (filter by status)
    // ══════════════════════════════════════════════════════════════
    public function allPayments(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = DB::table('lawyer_commission_payments as cp')
            ->join('lawyers as l', 'l.id', '=', 'cp.lawyer_id')
            ->orderByDesc('cp.submitted_at')
            ->select([
                'cp.id', 'cp.ref_code', 'cp.amount', 'cp.method',
                'cp.transaction_ref', 'cp.status', 'cp.submitted_at',
                'cp.reviewed_at', 'cp.admin_note',
                'l.id as lawyer_id', 'l.full_name', 'l.lawyer_code',
            ]);

        if ($status !== 'all') {
            $query->where('cp.status', $status);
        }

        return response()->json([
            'success'  => true,
            'payments' => $query->limit(50)->get(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /api/admin/commission/{refCode}/approve
    // Admin: commission payment approve করো → lawyer balance adjust
    // ══════════════════════════════════════════════════════════════
    public function approvePayment(string $refCode)
    {
        $payment = DB::table('lawyer_commission_payments')
            ->where('ref_code', $refCode)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found or already reviewed.'], 404);
        }

        DB::transaction(function () use ($payment, $refCode) {
            // ① Payment approved করো
            DB::table('lawyer_commission_payments')
                ->where('ref_code', $refCode)
                ->update([
                    'status'      => 'approved',
                    'reviewed_at' => now(),
                ]);

            // ② Lawyer এর total_commission_paid বাড়াও (balance adjust)
            DB::table('lawyers')
                ->where('id', $payment->lawyer_id)
                ->increment('total_commission_paid', $payment->amount);

            // ③ Lawyer কে notification পাঠাও
            DB::table('lawyer_notifications')->insert([
                'lawyer_id'  => $payment->lawyer_id,
                'type'       => 'commission_approved',
                'title'      => '✅ Commission Payment Approved',
                'body'       => "Your commission payment of ৳" . number_format($payment->amount, 2)
                              . " (Ref: {$refCode}) via " . strtoupper($payment->method)
                              . " has been verified and approved by admin. Your account balance has been updated.",
                'data'       => json_encode(['ref_code' => $refCode, 'amount' => $payment->amount]),
                'is_read'    => false,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Commission payment {$refCode} approved. Lawyer balance has been adjusted.",
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /api/admin/commission/{refCode}/reject
    // Admin: commission payment reject করো
    // ══════════════════════════════════════════════════════════════
    public function rejectPayment(Request $request, string $refCode)
    {
        $payment = DB::table('lawyer_commission_payments')
            ->where('ref_code', $refCode)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found or already reviewed.'], 404);
        }

        $note = $request->input('note', 'Transaction could not be verified.');

        DB::transaction(function () use ($payment, $refCode, $note) {
            DB::table('lawyer_commission_payments')
                ->where('ref_code', $refCode)
                ->update([
                    'status'      => 'rejected',
                    'admin_note'  => $note,
                    'reviewed_at' => now(),
                ]);

            // Lawyer কে notification পাঠাও
            DB::table('lawyer_notifications')->insert([
                'lawyer_id'  => $payment->lawyer_id,
                'type'       => 'commission_rejected',
                'title'      => '❌ Commission Payment Rejected',
                'body'       => "Your commission payment of ৳" . number_format($payment->amount, 2)
                              . " (Ref: {$refCode}) was rejected. Reason: {$note}. Please resubmit with correct transaction details.",
                'data'       => json_encode(['ref_code' => $refCode, 'amount' => $payment->amount, 'note' => $note]),
                'is_read'    => false,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Payment {$refCode} rejected. Lawyer has been notified.",
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /api/admin/notifications
    // Admin notification panel — lawyer disputes + commission payments
    // ══════════════════════════════════════════════════════════════
    public function adminNotifications(Request $request)
    {
        $notifications = DB::table('user_notifications')
            ->where('user_id', 1) // admin
            ->whereIn('type', ['commission_payment', 'lawyer_admin_contact'])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unreadCount = DB::table('user_notifications')
            ->where('user_id', 1)
            ->whereIn('type', ['commission_payment', 'lawyer_admin_contact'])
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /api/admin/notifications/mark-read
    // ══════════════════════════════════════════════════════════════
    public function markNotificationsRead()
    {
        DB::table('user_notifications')
            ->where('user_id', 1)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function generateRefCode(): string
    {
        $date  = now()->format('Ymd');
        $today = now()->startOfDay();
        $count = DB::table('lawyer_commission_payments')
                    ->where('submitted_at', '>=', $today)
                    ->count() + 1;
        return 'COMM-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

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
}