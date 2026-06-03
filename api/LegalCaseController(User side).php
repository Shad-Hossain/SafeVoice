<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LegalCase;
use App\Models\LegalCaseOffer;
use App\Models\LegalPayment;
use App\Models\LegalCaseMessage;
use App\Models\LegalNotification;
use App\Models\Lawyer;
use Illuminate\Support\Facades\Mail;

class LegalCaseController extends Controller
{
    // ─── Case Submit ─────────────────────────────────────────────

    public function submit(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $request->validate([
            'issue_type'   => 'required|string|max:50',
            'description'  => 'required|string|min:20',
            'user_budget'  => 'required|numeric|min:1',
            'user_consent' => 'required|accepted',
        ]);

        // Evidence files upload
        $evidenceFiles = [];
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                $path = $file->store('legal_evidence', 'public');
                $evidenceFiles[] = $path;
            }
        }

        // Unique case ID generate
        $caseId = 'LC-' . date('Y') . '-' . str_pad(LegalCase::count() + 1, 4, '0', STR_PAD_LEFT);

        $case = LegalCase::create([
            'case_id'        => $caseId,
            'user_id'        => $userId,
            'issue_type'     => $request->issue_type,
            'description'    => $request->description,
            'user_budget'    => $request->user_budget,
            'contact_phone'  => $request->contact_phone,
            'preferred_time' => $request->preferred_time,
            'evidence_files' => $evidenceFiles,
            'user_consent'   => true,
            'status'         => 'pending',
            'offer_expires_at' => now()->addHours(48),
        ]);

        // সব active lawyer কে notification
        $lawyers = Lawyer::where('status', 'active')->get();
        foreach ($lawyers as $lawyer) {
            LegalNotification::notify(
                'lawyer', $lawyer->id, $case->id,
                'নতুন Legal Case',
                "একটি নতুন {$case->issue_type} case submit হয়েছে। Budget: ৳{$case->user_budget}",
                'new_case'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Case submit হয়েছে। Lawyer খোঁজা হচ্ছে।',
            'case_id' => $caseId,
        ]);
    }

    // ─── User এর Cases List ───────────────────────────────────────

    public function myCases(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $cases = LegalCase::where('user_id', $userId)
                          ->with(['lawyer', 'offers.lawyer', 'payments'])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($case) {
                              return [
                                  'id'           => $case->id,
                                  'case_id'      => $case->case_id,
                                  'issue_type'   => $case->issue_type,
                                  'status'       => $case->status,
                                  'status_label' => $case->status_label,
                                  'user_budget'  => $case->user_budget,
                                  'agreed_price' => $case->agreed_price,
                                  'lawyer'       => $case->lawyer ? [
                                      'name'           => $case->lawyer->name,
                                      'phone'          => $case->lawyer->phone,
                                      'email'          => $case->lawyer->email,
                                      'specialization' => $case->lawyer->specialization,
                                      'rating'         => $case->lawyer->rating,
                                  ] : null,
                                  'pending_offers'   => $case->offers->where('status', 'pending')->values(),
                                  'payment_deadline' => $case->payment_deadline,
                                  'created_at'       => $case->created_at,
                              ];
                          });

        return response()->json(['success' => true, 'cases' => $cases]);
    }

    // ─── User: Offer Accept/Reject ────────────────────────────────

    public function respondToOffer(Request $request, $offerId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $offer = LegalCaseOffer::with('legalCase', 'lawyer')->findOrFail($offerId);

        if ($offer->legalCase->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        $action = $request->input('action'); // 'accept' or 'reject'

        if ($action === 'accept') {
            $offer->update(['status' => 'accepted_by_user']);

            $price = $offer->counter_price ?? $offer->legalCase->user_budget;

            $offer->legalCase->update([
                'status'              => 'offer_received',
                'lawyer_id'           => $offer->lawyer_id,
                'agreed_price'        => $price,
                'lawyer_assigned_at'  => now(),
            ]);

            // Lawyer কে notification
            LegalNotification::notify(
                'lawyer', $offer->lawyer_id, $offer->legal_case_id,
                'Offer Accept হয়েছে!',
                "User আপনার offer accept করেছেন। Case: {$offer->legalCase->case_id}",
                'offer_accepted'
            );

            return response()->json([
                'success' => true,
                'message' => 'Offer accept হয়েছে। এখন ৩০% payment করুন।',
                'agreed_price' => $price,
                'amount_30_percent' => round($price * 0.3, 2),
            ]);
        }

        if ($action === 'reject') {
            $offer->update(['status' => 'rejected_by_user']);

            return response()->json(['success' => true, 'message' => 'Offer reject করা হয়েছে।']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action।'], 400);
    }

    // ─── User: 30% Payment ───────────────────────────────────────

    public function pay30Percent(Request $request, $caseId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        if ($case->is30PercentPaid()) {
            return response()->json(['success' => false, 'message' => '৩০% আগেই পেমেন্ট হয়েছে।'], 400);
        }

        $amount = round($case->agreed_price * 0.3, 2);

        LegalPayment::create([
            'legal_case_id'  => $case->id,
            'user_id'        => $userId,
            'lawyer_id'      => $case->lawyer_id,
            'payment_type'   => '30_percent',
            'amount'         => $amount,
            'transaction_id' => $request->transaction_id ?? 'TXN-' . time(),
            'status'         => 'completed',
            'paid_at'        => now(),
        ]);

        $case->update(['status' => 'lawyer_booked']);

        // Lawyer কে notification
        LegalNotification::notify(
            'lawyer', $case->lawyer_id, $case->id,
            'Booking Confirmed!',
            "User ৩০% payment করেছেন (৳{$amount})। Case: {$case->case_id}",
            'payment_30_done'
        );

        return response()->json([
            'success' => true,
            'message' => 'Lawyer book হয়েছে! Case শুরু হবে।',
        ]);
    }

    // ─── User: 70% Payment ───────────────────────────────────────

    public function pay70Percent(Request $request, $caseId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        if ($case->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'এখন payment করার সময় হয়নি।'], 400);
        }

        $amount = round($case->agreed_price * 0.7, 2);

        LegalPayment::create([
            'legal_case_id'  => $case->id,
            'user_id'        => $userId,
            'lawyer_id'      => $case->lawyer_id,
            'payment_type'   => '70_percent',
            'amount'         => $amount,
            'transaction_id' => $request->transaction_id ?? 'TXN-' . time(),
            'status'         => 'completed',
            'paid_at'        => now(),
        ]);

        $case->update(['status' => 'resolved']);

        // Lawyer কে notification
        LegalNotification::notify(
            'lawyer', $case->lawyer_id, $case->id,
            'Final Payment Done!',
            "User বাকি ৭০% payment করেছেন। Case resolved।",
            'payment_70_done'
        );

        return response()->json([
            'success' => true,
            'message' => 'সম্পূর্ণ payment হয়েছে। Case resolved!',
        ]);
    }

    // ─── User: Admin কে Dispute/Complaint ────────────────────────

    public function disputeCase(Request $request, $caseId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::with('lawyer', 'user')->findOrFail($caseId);

        if ($case->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        $request->validate(['reason' => 'required|string|min:20']);

        $case->update(['status' => 'disputed']);

        // Admin কে email
        $adminEmail = config('mail.admin_email', 'admin@safevoice.com');

        try {
            Mail::raw(
                "Dispute Complaint\n\n" .
                "Case ID: {$case->case_id}\n" .
                "User: {$case->user->name} ({$case->user->email})\n" .
                "Lawyer: {$case->lawyer->name} ({$case->lawyer->email})\n" .
                "Agreed Price: ৳{$case->agreed_price}\n\n" .
                "Reason:\n{$request->reason}\n\n" .
                "Admin কে ৪৮ ঘন্টার মধ্যে reply করতে হবে।",
                fn($m) => $m->to($adminEmail)->subject("SafeVoice — Legal Case Dispute: {$case->case_id}")
            );
        } catch (\Exception $e) {}

        LegalNotification::notify(
            'admin', 1, $case->id,
            'Legal Case Dispute!',
            "Case {$case->case_id} এ user complaint করেছেন। ৪৮ ঘন্টার মধ্যে reply দিন।",
            'case_disputed'
        );

        return response()->json([
            'success' => true,
            'message' => 'Complaint admin কে পাঠানো হয়েছে। ৪৮ ঘন্টার মধ্যে reply পাবেন।',
        ]);
    }

    // ─── Messages (User → Lawyer) ─────────────────────────────────

    public function sendMessage(Request $request, $caseId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        if (!in_array($case->status, ['lawyer_booked', 'in_progress', 'waiting_payment'])) {
            return response()->json(['success' => false, 'message' => 'এখন message করা যাবে না।'], 400);
        }

        $request->validate(['message' => 'required|string']);

        LegalCaseMessage::create([
            'legal_case_id' => $case->id,
            'sender_type'   => 'user',
            'sender_id'     => $userId,
            'message'       => $request->message,
        ]);

        return response()->json(['success' => true, 'message' => 'Message পাঠানো হয়েছে।']);
    }

    // ─── Messages List ────────────────────────────────────────────

    public function getMessages(Request $request, $caseId)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $case = LegalCase::findOrFail($caseId);

        if ($case->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized।'], 403);
        }

        $messages = $case->messages()->get()->map(function ($msg) {
            return [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'message'     => $msg->message,
                'created_at'  => $msg->created_at,
            ];
        });

        // Lawyer এর messages mark as read
        LegalCaseMessage::where('legal_case_id', $case->id)
                        ->where('sender_type', 'lawyer')
                        ->where('is_read', false)
                        ->update(['is_read' => true]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    // ─── User Notifications ───────────────────────────────────────

    public function myNotifications(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $notifications = LegalNotification::where('recipient_type', 'user')
                                          ->where('recipient_id', $userId)
                                          ->orderBy('created_at', 'desc')
                                          ->take(20)
                                          ->get();

        LegalNotification::where('recipient_type', 'user')
                         ->where('recipient_id', $userId)
                         ->where('is_read', false)
                         ->update(['is_read' => true]);

        return response()->json(['success' => true, 'notifications' => $notifications]);
    }
}
