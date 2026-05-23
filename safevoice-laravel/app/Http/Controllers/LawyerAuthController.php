<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Lawyer;

class LawyerAuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/register
    //
    // Flutter app থেকে:
    //   - id_card (image file) — required
    //   - email, phone, password — required
    //   - specialization, experience_years, bio, chamber_address — optional
    //
    // Flow:
    //   1. ID card image save করো
    //   2. Tesseract OCR দিয়ে text extract করো
    //   3. Bar Council ID এবং নাম auto-extract
    //   4. Account status = 'pending' (admin verify করবে)
    // ══════════════════════════════════════════════════════════════════
    public function register(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|unique:lawyers,email',
            'phone'    => 'required|string|unique:lawyers,phone',
            'password' => 'required|min:8',
            'id_card'  => 'required|file|mimes:jpg,jpeg,png|max:5120', // max 5MB
        ]);

        // ── 1. ID card image save ─────────────────────────────────────
        $file      = $request->file('id_card');
        $ext       = strtolower($file->getClientOriginalExtension());
        $filename  = 'lawyer_id_' . uniqid() . '.' . $ext;
        $file->move(public_path('uploads/lawyer_ids'), $filename);
        $idCardPath = 'uploads/lawyer_ids/' . $filename;

        // ── 2. Tesseract OCR ──────────────────────────────────────────
        $absolutePath = public_path($idCardPath);
        $ocrText      = $this->runOcr($absolutePath);

        // ── 3. Extract Bar Council ID এবং নাম ────────────────────────
        $extractedId   = $this->extractBarCouncilId($ocrText);
        $extractedName = $this->extractName($ocrText);

        // OCR fail করলেও register হবে, admin manually verify করবে
        // তবে যদি same bar_council_id already থাকে reject করো
        if ($extractedId && Lawyer::where('bar_council_id', $extractedId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A lawyer account with this Bar Council ID already exists.',
            ], 422);
        }

        // ── 4. Lawyer create ──────────────────────────────────────────
        $lawyer = Lawyer::create([
            'bar_council_id' => $extractedId ?? 'PENDING_OCR_' . uniqid(),
            'name_from_id'   => $extractedName ?? 'Pending Verification',
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password_hash'  => Hash::make($request->password),
            'id_card_path'   => $idCardPath,
            'display_name'   => $request->display_name ?? null,
            'specialization' => $request->specialization ?? null,
            'bio'            => $request->bio ?? null,
            'chamber_address'=> $request->chamber_address ?? null,
            'experience_years' => $request->experience_years ?? 0,
            'status'         => 'pending',  // Admin approve করলে তবেই login করতে পারবে
        ]);

        return response()->json([
            'success'       => true,
            'pending'       => true,
            'message'       => 'Registration submitted! Your Bar Council ID card is being reviewed by our admin team. You will receive a notification once approved.',
            'ocr_extracted' => [
                'bar_council_id' => $extractedId,
                'name'           => $extractedName,
                'ocr_success'    => !is_null($extractedId) && !is_null($extractedName),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/login
    // ══════════════════════════════════════════════════════════════════
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $lawyer = Lawyer::where('email', $request->email)->first();

        if (!$lawyer || !Hash::check($request->password, $lawyer->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Status check
        if ($lawyer->status === 'pending') {
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'Your account is pending admin approval. Please wait — you will be notified once your Bar Council ID is verified.',
            ], 403);
        }

        if ($lawyer->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your registration was rejected. Reason: ' . ($lawyer->admin_note ?? 'Please contact support.'),
            ], 403);
        }

        if ($lawyer->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact admin.',
            ], 403);
        }

        // Session set
        $request->session()->put('lawyer_id',    $lawyer->id);
        $request->session()->put('lawyer_email', $lawyer->email);
        $request->session()->put('is_lawyer',    true);

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'lawyer'  => $this->lawyerProfile($lawyer),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/logout
    // ══════════════════════════════════════════════════════════════════
    public function logout(Request $request)
    {
        $request->session()->forget(['lawyer_id', 'lawyer_email', 'is_lawyer']);
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/lawyer/profile
    // ══════════════════════════════════════════════════════════════════
    public function profile(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return response()->json([
            'success' => true,
            'lawyer'  => $this->lawyerProfile($lawyer),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/profile/update
    //
    // Note: bar_council_id এবং name_from_id update করা যাবে না
    //       শুধু display_name, specialization, bio, etc. update করা যাবে
    // ══════════════════════════════════════════════════════════════════
    public function updateProfile(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'display_name'    => 'nullable|string|max:150',
            'specialization'  => 'nullable|string|max:150',
            'bio'             => 'nullable|string|max:1000',
            'chamber_address' => 'nullable|string|max:500',
            'experience_years'=> 'nullable|integer|min:0|max:60',
        ]);

        // !! bar_council_id এবং name_from_id এই endpoint থেকে update করা যাবে না
        $lawyer->update($request->only([
            'display_name', 'specialization', 'bio',
            'chamber_address', 'experience_years',
        ]));

        // Profile photo update
        if ($request->hasFile('profile_photo')) {
            $file     = $request->file('profile_photo');
            $filename = 'lawyer_photo_' . $lawyer->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/lawyer_photos'), $filename);
            $lawyer->update(['profile_photo' => 'uploads/lawyer_photos/' . $filename]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'lawyer'  => $this->lawyerProfile($lawyer->fresh()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/lawyer/fcm-token
    // ══════════════════════════════════════════════════════════════════
    public function updateFcmToken(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate(['fcm_token' => 'required|string']);
        $lawyer->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Tesseract OCR দিয়ে image থেকে text extract করো
     * Server এ tesseract এবং tesseract-ocr-ben install থাকতে হবে:
     *   sudo apt install tesseract-ocr tesseract-ocr-ben
     */
    private function runOcr(string $imagePath): string
    {
        if (!file_exists($imagePath)) return '';

        // বাংলা + ইংরেজি দুটোই try করো
        $cmd    = 'tesseract ' . escapeshellarg($imagePath) . ' stdout -l ben+eng 2>/dev/null';
        $output = shell_exec($cmd);

        return $output ?? '';
    }

    /**
     * OCR text থেকে Bar Council ID extract করো
     * Bangladesh Bar Council ID format: numeric, সাধারণত 4-8 digit
     * Pattern adjust করো actual ID card দেখে
     */
    private function extractBarCouncilId(string $text): ?string
    {
        if (empty($text)) return null;

        // Common patterns in Bangladesh Bar Council ID cards
        $patterns = [
            '/Enrollment\s*No[:\.]?\s*(\d{4,10})/i',
            '/Enrolment\s*No[:\.]?\s*(\d{4,10})/i',
            '/ID\s*No[:\.]?\s*(\d{4,10})/i',
            '/নথি\s*নম্বর[:\s]*(\d{4,10})/u',
            '/ক্রমিক\s*নং[:\s]*(\d{4,10})/u',
            '/\b(\d{5,8})\b/',  // fallback: 5-8 digit standalone number
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return trim($match[1]);
            }
        }

        return null;
    }

    /**
     * OCR text থেকে নাম extract করো
     */
    private function extractName(string $text): ?string
    {
        if (empty($text)) return null;

        $patterns = [
            '/Name\s*[:\.]?\s*([A-Za-z\s\.]+)/i',
            '/নাম[:\s]*([^\n\r]+)/u',
            '/Advocate\s+([A-Za-z\s\.]+)/i',
            '/Mr\.?\s+([A-Za-z\s\.]+)/i',
            '/Mrs\.?\s+([A-Za-z\s\.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $name = trim($match[1]);
                // Noise filter: অনেক বড় বা অনেক ছোট না হলে accept
                if (strlen($name) >= 3 && strlen($name) <= 100) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Session থেকে authenticated lawyer get করো
     */
    private function getAuthLawyer(Request $request): ?Lawyer
    {
        $lawyerId = $request->session()->get('lawyer_id');
        if (!$lawyerId) return null;

        return Lawyer::where('id', $lawyerId)
                     ->where('status', 'approved')
                     ->first();
    }

    /**
     * Lawyer profile array (response format)
     */
    private function lawyerProfile(Lawyer $lawyer): array
    {
        return [
            'id'               => $lawyer->id,
            'bar_council_id'   => $lawyer->bar_council_id,   // read-only
            'name_from_id'     => $lawyer->name_from_id,     // read-only (OCR)
            'display_name'     => $lawyer->display_name,
            'public_name'      => $lawyer->display_name ?? $lawyer->name_from_id,
            'email'            => $lawyer->email,
            'phone'            => $lawyer->phone,
            'specialization'   => $lawyer->specialization,
            'bio'              => $lawyer->bio,
            'chamber_address'  => $lawyer->chamber_address,
            'experience_years' => $lawyer->experience_years,
            'profile_photo'    => $lawyer->profile_photo,
            'status'           => $lawyer->status,
            'total_cases'      => $lawyer->total_cases,
            'active_cases'     => $lawyer->active_cases,
            'rating'           => $lawyer->rating,
            'rating_count'     => $lawyer->rating_count,
            'approved_at'      => $lawyer->approved_at,
        ];
    }
}
