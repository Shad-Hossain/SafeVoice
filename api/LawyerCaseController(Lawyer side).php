<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LegalCase;
use App\Models\LegalCaseOffer;
use App\Models\LegalCaseMessage;
use App\Models\LegalNotification;

class LawyerCaseController extends Controller
{
    // ─── Available Cases List ─────────────────────────────────────

    public function availableCases(Request $request)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $cases = LegalCase::where('status', 'pending')
                          ->where('offer_expires_at', '>', now())
                          ->whereDoesntHave('offers', function ($q) use ($lawyerId) {
                              $q->where('lawyer_id', $lawyerId);
                          })
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(fn($c) => [
                              'id'           => $c->id,
                              'case_id'      => $c->case_id,
                              'issue_type'   => $c->issue_type,
                              'description'  => substr($c->description, 0, 200) . '...',
                              'user_budget'  => $c->user_budget,
                              'created_at'   => $c->created_at,
                              'expires_at'   => $c->offer_expires_at,
                          ]);

        return response()->json(['success' => true, 'cases' => $cases]);
    }

    // ─── Lawyer: Accept / Reject / Counter ───────────────────────

    public function makeOffer(Request $request, $caseId)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Case আর available নেই।'], 400);
        }

        // Already offer দিয়েছে কিনা check
        $existing = LegalCaseOffer::where('legal_case_id', $caseId)
                                  ->where('lawyer_id', $lawyerId)
                                  ->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'আগেই offer দিয়েছেন।'], 400);
        }

        $request->validate(['type' => 'required|in:accept,reject,counter']);

        if ($request->type === 'counter') {
            $request->validate(['counter_price' => 'required|numeric|min:1']);
        }

        $offer = LegalCaseOffer::create([
            'legal_case_id' => $caseId,
            'lawyer_id'     => $lawyerId,
            'type'          => $request->type,
            'counter_price' => $request->counter_price ?? null,
            'message'       => $request->message ?? null,
            'status'        => 'pending',
        ]);

        if ($request->type !== 'reject') {
            // User কে notification
            $priceText = $request->type === 'counter'
                ? "Counter offer: ৳{$request->counter_price}"
                : "Budget: ৳{$case->user_budget}";

            LegalNotification::notify(
                'user', $case->user_id, $case->id,
                'Lawyer Offer পেয়েছেন!',
                "একজন lawyer আপনার case accept করতে চান। {$priceText}",
                'offer_received'
            );

            $case->update(['status' => 'offer_received']);
        }

        return response()->json(['success' => true, 'message' => 'Offer পাঠানো হয়েছে।']);
    }

    // ─── Lawyer: Case Complete Mark ───────────────────────────────

    public function markComplete(Request $request, $caseId)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->lawyer_id != $lawyerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        if ($case->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Case in_progress state এ নেই।'], 400);
        }

        $deadline = now()->addDays(10);

        $case->update([
            'status'           => 'waiting_payment',
            'completed_at'     => now(),
            'payment_deadline' => $deadline,
        ]);

        // User কে notification — 10 দিনের timer শুরু
        LegalNotification::notify(
            'user', $case->user_id, $case->id,
            'Case Complete! Payment করুন।',
            "Lawyer case complete করেছেন। {$deadline->format('d M Y')} এর মধ্যে বাকি ৭০% payment করুন।",
            'case_completed'
        );

        return response()->json([
            'success'  => true,
            'message'  => 'Case complete mark হয়েছে।',
            'deadline' => $deadline->format('Y-m-d'),
        ]);
    }

    // ─── Lawyer: My Cases ─────────────────────────────────────────

    public function myCases(Request $request)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $cases = LegalCase::where('lawyer_id', $lawyerId)
                          ->with(['user', 'payments'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(fn($c) => [
                              'id'             => $c->id,
                              'case_id'        => $c->case_id,
                              'issue_type'     => $c->issue_type,
                              'status'         => $c->status,
                              'status_label'   => $c->status_label,
                              'agreed_price'   => $c->agreed_price,
                              'user_name'      => $c->user->name ?? '-',
                              'user_phone'     => $c->user->phone ?? '-',
                              'payment_deadline' => $c->payment_deadline,
                              'created_at'     => $c->created_at,
                          ]);

        return response()->json(['success' => true, 'cases' => $cases]);
    }

    // ─── Lawyer: Send Message ─────────────────────────────────────

    public function sendMessage(Request $request, $caseId)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->lawyer_id != $lawyerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        $request->validate(['message' => 'required|string']);

        LegalCaseMessage::create([
            'legal_case_id' => $case->id,
            'sender_type'   => 'lawyer',
            'sender_id'     => $lawyerId,
            'message'       => $request->message,
        ]);

        return response()->json(['success' => true, 'message' => 'Message পাঠানো হয়েছে।']);
    }

    // ─── Lawyer Notifications ─────────────────────────────────────

    public function myNotifications(Request $request)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $notifications = LegalNotification::where('recipient_type', 'lawyer')
                                          ->where('recipient_id', $lawyerId)
                                          ->orderBy('created_at', 'desc')
                                          ->take(20)
                                          ->get();

        LegalNotification::where('recipient_type', 'lawyer')
                         ->where('recipient_id', $lawyerId)
                         ->where('is_read', false)
                         ->update(['is_read' => true]);

        return response()->json(['success' => true, 'notifications' => $notifications]);
    }
}
