<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lawyer;
use App\Models\LegalNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class LawyerAuthController extends Controller
{
    // ─── Registration ───────────────────────────────────────────

    public function register(Request $request)
    {
        $request->validate([
            'email'          => 'required|email|unique:lawyers,email',
            'phone'          => 'required|unique:lawyers,phone',
            'password'       => 'required|min:6',
            'bar_council_id' => 'required|unique:lawyers,bar_council_id',
            'id_card'        => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // ID Card save
        $idCardPath = $request->file('id_card')->store('lawyer_id_cards', 'public');

        // OCR থেকে name & bar_council_id extract
        $extracted = $this->extractFromIdCard(storage_path('app/public/' . $idCardPath));

        $lawyer = Lawyer::create([
            'name'           => $extracted['name'] ?? $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password_hash'  => Hash::make($request->password),
            'bar_council_id' => $extracted['bar_council_id'] ?? $request->bar_council_id,
            'id_card_path'   => $idCardPath,
            'specialization' => $request->specialization,
            'bio'            => $request->bio,
            'status'         => 'pending',
            'id_verified'    => false,
        ]);

        // Admin কে notification
        LegalNotification::notify(
            'admin', 1, null,
            'নতুন Lawyer Registration',
            "{$lawyer->name} ({$lawyer->bar_council_id}) registration করেছেন। ID verify করুন।",
            'lawyer_registration'
        );

        return response()->json([
            'success' => true,
            'message' => 'Registration সফল! Admin verification এর পর account active হবে।',
        ]);
    }

    // ─── OCR — ID Card থেকে name & ID extract ───────────────────

    private function extractFromIdCard(string $imagePath): array
    {
        // Tesseract OCR দিয়ে text extract
        // Server এ tesseract install থাকলে কাজ করবে
        // না থাকলে empty array return করবে, admin manually verify করবে

        try {
            $output = shell_exec("tesseract " . escapeshellarg($imagePath) . " stdout 2>/dev/null");

            if (!$output) return [];

            $name = null;
            $barId = null;

            // Name extract — "Name:" বা "নাম:" এর পরের line
            if (preg_match('/(?:Name|নাম)[:\s]+([A-Za-z\s\x{0980}-\x{09FF}]+)/u', $output, $m)) {
                $name = trim($m[1]);
            }

            // Bar Council ID — সাধারণত numeric/alphanumeric
            if (preg_match('/(?:Bar Council|BC|ID)[:\s#]*([A-Z0-9\-]+)/i', $output, $m)) {
                $barId = trim($m[1]);
            }

            return ['name' => $name, 'bar_council_id' => $barId];

        } catch (\Exception $e) {
            return [];
        }
    }

    // ─── Login ───────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $lawyer = Lawyer::where('email', $request->email)->first();

        if (!$lawyer || !Hash::check($request->password, $lawyer->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Email বা Password ভুল।'], 401);
        }

        if ($lawyer->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Account এখনো verify হয়নি। Admin approval এর জন্য অপেক্ষা করুন।'], 403);
        }

        if ($lawyer->status === 'suspended' || $lawyer->status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Account suspended বা rejected।'], 403);
        }

        $request->session()->put('lawyer_id', $lawyer->id);
        $request->session()->put('lawyer_name', $lawyer->name);

        return response()->json([
            'success' => true,
            'message' => 'Login সফল!',
            'lawyer'  => [
                'id'             => $lawyer->id,
                'name'           => $lawyer->name,
                'email'          => $lawyer->email,
                'specialization' => $lawyer->specialization,
            ]
        ]);
    }

    // ─── Logout ──────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $request->session()->forget(['lawyer_id', 'lawyer_name']);
        return response()->json(['success' => true, 'message' => 'Logout সফল।']);
    }

    // ─── Profile ─────────────────────────────────────────────────

    public function profile(Request $request)
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) {
            return response()->json(['success' => false, 'message' => 'Login করুন।'], 401);
        }

        $lawyer = Lawyer::find($lawyerId);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Lawyer পাওয়া যায়নি।'], 404);
        }

        return response()->json([
            'success' => true,
            'lawyer'  => $lawyer,
            'unread_notifications' => $lawyer->unreadNotificationsCount(),
        ]);
    }

    // ─── Admin: Verify Lawyer ────────────────────────────────────

    public function verifyLawyer(Request $request, $lawyerId)
    {
        $lawyer = Lawyer::findOrFail($lawyerId);

        $action = $request->input('action'); // 'approve' or 'reject'

        if ($action === 'approve') {
            $lawyer->update([
                'status'      => 'active',
                'id_verified' => true,
                'verified_at' => now(),
                'verified_by' => 1,
            ]);

            // Lawyer কে email
            try {
                Mail::raw(
                    "আপনার SafeVoice Lawyer Account verify হয়েছে। এখন login করুন।",
                    fn($m) => $m->to($lawyer->email)->subject('SafeVoice — Account Approved')
                );
            } catch (\Exception $e) {}

            LegalNotification::notify(
                'lawyer', $lawyer->id, null,
                'Account Approved!',
                'আপনার account verify হয়েছে। এখন cases দেখতে পারবেন।',
                'account_approved'
            );

            return response()->json(['success' => true, 'message' => 'Lawyer approved।']);
        }

        if ($action === 'reject') {
            $lawyer->update(['status' => 'rejected']);

            try {
                Mail::raw(
                    "দুঃখিত, আপনার SafeVoice Lawyer Account reject হয়েছে। সঠিক ID দিয়ে আবার চেষ্টা করুন।",
                    fn($m) => $m->to($lawyer->email)->subject('SafeVoice — Account Rejected')
                );
            } catch (\Exception $e) {}

            return response()->json(['success' => true, 'message' => 'Lawyer rejected।']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action।'], 400);
    }

    // ─── Admin: Pending Lawyers List ─────────────────────────────

    public function pendingLawyers()
    {
        $lawyers = Lawyer::where('status', 'pending')
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json(['success' => true, 'lawyers' => $lawyers]);
    }
}
