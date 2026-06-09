<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Lawyer;
use App\Models\AdminActionLog;
use App\Helpers\BangladeshAreas;

class LawyerAuthController extends Controller
{
    // ── POST /api/lawyer/register ──────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'full_name'          => 'required|string|max:100',
            'email'              => 'required|email|unique:lawyers,email',
            'phone'              => 'required|string|unique:lawyers,phone',
            'password'           => 'required|min:8',
            'bar_council_id'     => 'required|string|unique:lawyers,bar_council_id',
            // ── Bar Council card — mandatory for identity verification ──
            'bar_council_photo'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'profile_photo'      => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'specializations'    => 'nullable|array',
            'experience_years'   => 'nullable|integer|min:0|max:60',
            'city'               => 'nullable|string|max:100',
            // ── Phase 1: Location fields ──────────────────────────────
            'division'           => 'nullable|string|max:100',
            'serving_areas'      => 'required|array|min:1',
            'serving_areas.*'    => 'required|string|max:100',
        ]);

        // Validate division against known list
        if ($request->filled('division')) {
            $validDivisions = BangladeshAreas::divisions();
            if (!in_array($request->division, $validDivisions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid division. Valid options: ' . implode(', ', $validDivisions),
                ], 422);
            }
        }

        // Validate each serving_area against known districts
        if ($request->filled('serving_areas')) {
            $allDistricts = BangladeshAreas::allDistricts();
            $invalid = array_diff($request->serving_areas, $allDistricts);
            if (!empty($invalid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid serving areas: ' . implode(', ', $invalid),
                ], 422);
            }
        }

        // ── Photo uploads ───────────────────────────────────────────
        $bcFile          = $request->file('bar_council_photo');
        $bcExt           = strtolower($bcFile->getClientOriginalExtension());
        $barCouncilPhoto = 'uploads/lawyers/bar_' . uniqid() . '.' . $bcExt;
        $bcFile->move(public_path('uploads/lawyers'), basename($barCouncilPhoto));

        $profilePhoto = null;
        if ($request->hasFile('profile_photo')) {
            $file         = $request->file('profile_photo');
            $ext          = strtolower($file->getClientOriginalExtension());
            $profilePhoto = 'uploads/lawyers/lp_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/lawyers'), basename($profilePhoto));
        }

        // ── OCR: Bar Council card থেকে ID + Name auto-extract ──────
        $ocrBarId    = null;
        $ocrName     = null;
        $ocrMismatch = false;

        $ocrResult = $this->runOcr($barCouncilPhoto);
        if ($ocrResult['success']) {
            $ocrBarId = $ocrResult['bar_council_id'] ?? null;
            $ocrName  = $ocrResult['name'] ?? null;

            // User-provided ID vs OCR ID — soft mismatch warning (admin verify করবে)
            if ($ocrBarId && $request->filled('bar_council_id')) {
                $ocrMismatch = strtoupper(trim($request->bar_council_id)) !== strtoupper(trim($ocrBarId));
            }
        }

        $cleanPhone = preg_replace('/\D/', '', $request->phone);
        if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);

        $division = $request->division;
        if (!$division && $request->filled('serving_areas') && !empty($request->serving_areas)) {
            $division = BangladeshAreas::divisionOfDistrict($request->serving_areas[0]);
        }

        $lawyer = Lawyer::create([
            'lawyer_code'      => Lawyer::generateCode(),
            'full_name'        => $request->full_name,
            'email'            => strtolower(trim($request->email)),
            'email_hash'       => hash('sha256', strtolower(trim($request->email))),
            'phone'            => $cleanPhone,
            'password_hash'    => Hash::make($request->password),
            'bar_council_id'   => strtoupper(trim($request->bar_council_id)),
            'bar_council_photo'=> $barCouncilPhoto,
            'profile_photo'    => $profilePhoto,
            'address'          => $request->address ?? null,
            'city'             => $request->city ?? null,
            'division'         => $division,
            'serving_areas'    => $request->serving_areas ?? [],
            'specializations'  => $request->specializations ?? [],
            'experience_years' => $request->experience_years ?? 0,
            'min_fee'          => $request->min_fee ?? 500.00,
            'bio'              => $request->bio ?? null,
            'status'           => 'Pending',
        ]);

        return response()->json([
            'success'     => true,
            'pending'     => true,
            'message'     => 'Registration successful! Your account is under review. You\'ll be notified within 24-48 hours after admin approval.',
            'lawyer_code' => $lawyer->lawyer_code,
            'ocr_extracted' => $ocrBarId ? [
                'bar_council_id' => $ocrBarId,
                'name'           => $ocrName,
                'matched'        => !$ocrMismatch,
                'note'           => $ocrMismatch
                    ? 'OCR ID differs from submitted ID. Admin will verify.'
                    : 'OCR matched successfully.',
            ] : null,
        ]);
    }

    // ── POST /api/lawyer/login ─────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $key = 'lawyer_login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $secs = RateLimiter::availableIn($key);
            return response()->json(['success' => false, 'message' => "Too many attempts. Try again in {$secs}s."], 429);
        }

        $emailHash = hash('sha256', strtolower(trim($request->email)));
        $lawyer    = Lawyer::where('email_hash', $emailHash)
                          ->orWhere('email', $request->email)
                          ->first();

        if (!$lawyer || !Hash::check($request->password, $lawyer->password_hash)) {
            RateLimiter::hit($key, 60);
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        RateLimiter::clear($key);

        if ($lawyer->status === 'Pending') {
            return response()->json([
                'success' => false, 'pending' => true,
                'message' => 'Your account is under admin review. Please wait 24-48 hours.',
            ], 403);
        }

        if ($lawyer->status === 'Suspended' || $lawyer->status === 'Banned') {
            return response()->json([
                'success' => false,
                'message' => "Your account has been {$lawyer->status}. Contact support.",
            ], 403);
        }

        $lawyer->tokens()->delete();
        $token = $lawyer->createToken('lawyer_app')->plainTextToken;

        $request->session()->put('lawyer_id',   $lawyer->id);
        $request->session()->put('lawyer_name', $lawyer->full_name);
        $request->session()->put('lawyer_code', $lawyer->lawyer_code);

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'token'   => $token,
            'lawyer'  => $this->lawyerData($lawyer),
        ]);
    }

    // ── POST /api/lawyer/logout ────────────────────────────────────
    public function logout(Request $request)
    {
        try { $request->user('lawyer')?->currentAccessToken()->delete(); } catch (\Exception) {}
        $request->session()->forget(['lawyer_id', 'lawyer_name', 'lawyer_code']);
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    // ── GET /api/lawyer/profile ────────────────────────────────────
    public function profile(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        return response()->json(['success' => true, 'lawyer' => $this->lawyerData($lawyer)]);
    }

    // ── POST /api/lawyer/profile/update ───────────────────────────
    public function updateProfile(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if (!$lawyer) return response()->json(['success' => false], 401);

        // bar_council_id is permanently locked after registration
        if ($request->filled('bar_council_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Bar Council ID cannot be changed after registration. Contact admin if there is an error.',
            ], 422);
        }

        $updates = [];

        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', $request->phone);
            if (strlen($phone) === 13) $phone = substr($phone, 2);
            if (Lawyer::where('phone', $phone)->where('id', '!=', $lawyer->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Phone already registered.'], 422);
            }
            $updates['phone'] = $phone;
        }

        if ($request->filled('bio'))              $updates['bio']               = $request->bio;
        if ($request->filled('city'))             $updates['city']              = $request->city;
        if ($request->filled('address'))          $updates['address']           = $request->address;
        if ($request->filled('min_fee'))          $updates['min_fee']           = $request->min_fee;
        if ($request->filled('experience_years')) $updates['experience_years']  = $request->experience_years;
        if ($request->filled('specializations'))  $updates['specializations']   = $request->specializations;
        if (isset($request->is_available))        $updates['is_available']      = (bool) $request->is_available;

        // bar_council_id intentionally NOT in updates — non-editable after registration

        if ($request->filled('division')) {
            if (!in_array($request->division, BangladeshAreas::divisions())) {
                return response()->json(['success' => false, 'message' => 'Invalid division.'], 422);
            }
            $updates['division'] = $request->division;
        }

        if ($request->filled('serving_areas') && is_array($request->serving_areas)) {
            $invalid = array_diff($request->serving_areas, BangladeshAreas::allDistricts());
            if (!empty($invalid)) {
                return response()->json(['success' => false, 'message' => 'Invalid serving areas: ' . implode(', ', $invalid)], 422);
            }
            $updates['serving_areas'] = $request->serving_areas;

            if (!isset($updates['division']) && !empty($request->serving_areas)) {
                $detected = BangladeshAreas::divisionOfDistrict($request->serving_areas[0]);
                if ($detected) $updates['division'] = $detected;
            }
        }

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return response()->json(['success' => false, 'message' => 'Invalid photo format.'], 422);
            }
            $filename = 'lp_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/lawyers'), $filename);
            $updates['profile_photo'] = 'uploads/lawyers/' . $filename;
        }

        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'Nothing to update.'], 422);
        }

        $lawyer->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'lawyer'  => $this->lawyerData($lawyer->fresh()),
        ]);
    }

    // ── GET /api/lawyer/check-session ─────────────────────────────
    public function checkSession(Request $request)
    {
        $lawyer = $this->getAuthLawyer($request);
        if ($lawyer) {
            return response()->json(['success' => true, 'loggedIn' => true, 'lawyer' => $this->lawyerData($lawyer)]);
        }
        return response()->json(['success' => false, 'loggedIn' => false], 401);
    }

    // ── GET /api/areas ─────────────────────────────────────────────
    public function areas()
    {
        return response()->json([
            'success' => true,
            'areas'   => BangladeshAreas::forApi(),
        ]);
    }

    // ── ADMIN: সব lawyers list ─────────────────────────────────────
    public function allLawyers(Request $request)
    {
        $status   = $request->query('status', '');
        $search   = $request->query('search', '');
        $division = $request->query('division', '');
        $district = $request->query('district', '');

        $query = Lawyer::query();
        if ($status)   $query->where('status', $status);
        if ($division) $query->where('division', $division);
        if ($district) $query->whereJsonContains('serving_areas', $district);
        if ($search)   $query->where(function($q) use ($search) {
            $q->where('full_name',      'like', "%$search%")
              ->orWhere('email',        'like', "%$search%")
              ->orWhere('lawyer_code',  'like', "%$search%")
              ->orWhere('bar_council_id','like', "%$search%");
        });

        $lawyers = $query->orderByRaw("FIELD(status,'Pending','Active','Suspended','Banned')")
                         ->orderByDesc('id')
                         ->get()
                         ->map(fn($l) => $this->adminLawyerRow($l));

        return response()->json(['success' => true, 'lawyers' => $lawyers]);
    }

    // ── ADMIN: Pending lawyers ─────────────────────────────────────
    public function pendingLawyers(Request $request)
    {
        $lawyers = Lawyer::where('status', 'Pending')
                         ->orderByDesc('id')
                         ->get()
                         ->map(fn($l) => $this->adminLawyerRow($l));

        return response()->json(['success' => true, 'lawyers' => $lawyers, 'count' => $lawyers->count()]);
    }

    // ── ADMIN: Verify (approve/reject) a lawyer ────────────────────
    public function verifyLawyer(Request $request, int $lawyerId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:500',
        ]);

        $lawyer         = Lawyer::findOrFail($lawyerId);
        $previousStatus = $lawyer->status;
        $newStatus      = $request->action === 'approve' ? 'Active' : 'Banned';

        $lawyer->update(['status' => $newStatus]);

        // ── Log the action ─────────────────────────────────────────
        AdminActionLog::record(
            targetType:      'lawyer',
            targetId:        $lawyer->id,
            targetName:      $lawyer->full_name,
            action:          $request->action === 'approve' ? 'lawyer_approved' : 'lawyer_rejected',
            previousStatus:  $previousStatus,
            newStatus:       $newStatus,
            reason:          $request->reason,
            adminIdentifier: $request->session()->get('admin_email') ?? 'admin',
        );

        return response()->json([
            'success' => true,
            'message' => "Lawyer {$request->action}d successfully.",
            'status'  => $newStatus,
        ]);
    }

    // ── ADMIN: Suspend / Unsuspend a lawyer ────────────────────────
    public function toggleSuspend(Request $request, int $lawyerId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $lawyer         = Lawyer::findOrFail($lawyerId);
        $previousStatus = $lawyer->status;

        if ($lawyer->status === 'Suspended') {
            $lawyer->update(['status' => 'Active']);
            $newStatus = 'Active';
            $action    = 'lawyer_unsuspended';
            $msg       = 'Lawyer unsuspended.';
        } else {
            $lawyer->update(['status' => 'Suspended', 'is_available' => false]);
            $newStatus = 'Suspended';
            $action    = 'lawyer_suspended';
            $msg       = 'Lawyer suspended.';
        }

        // ── Log the action ─────────────────────────────────────────
        AdminActionLog::record(
            targetType:      'lawyer',
            targetId:        $lawyer->id,
            targetName:      $lawyer->full_name,
            action:          $action,
            previousStatus:  $previousStatus,
            newStatus:       $newStatus,
            reason:          $request->reason,
            adminIdentifier: $request->session()->get('admin_email') ?? 'admin',
        );

        return response()->json(['success' => true, 'message' => $msg, 'status' => $newStatus]);
    }

    // ── ADMIN: Permanently ban a lawyer ───────────────────────────
    // Active lawyer-কে directly ban করার endpoint (toggleSuspend শুধু suspend করে)
    public function banLawyer(Request $request, int $lawyerId)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $lawyer = Lawyer::findOrFail($lawyerId);

        if ($lawyer->status === 'Banned') {
            return response()->json([
                'success' => false,
                'message' => 'Lawyer is already banned.',
            ], 422);
        }

        $previousStatus = $lawyer->status;

        $lawyer->update([
            'status'       => 'Banned',
            'is_available' => false,
        ]);

        // Revoke all active tokens — force logout
        $lawyer->tokens()->delete();

        // ── Log the action ─────────────────────────────────────────
        AdminActionLog::record(
            targetType:      'lawyer',
            targetId:        $lawyer->id,
            targetName:      $lawyer->full_name,
            action:          'lawyer_banned',
            previousStatus:  $previousStatus,
            newStatus:       'Banned',
            reason:          $request->reason,
            adminIdentifier: $request->session()->get('admin_email') ?? 'admin',
        );

        return response()->json([
            'success' => true,
            'message' => "Lawyer {$lawyer->full_name} has been permanently banned.",
            'status'  => 'Banned',
        ]);
    }

    // ── ADMIN: Lawyer action history ──────────────────────────────
    public function lawyerActionHistory(int $lawyerId)
    {
        $lawyer = Lawyer::findOrFail($lawyerId);

        $logs = AdminActionLog::where('target_type', 'lawyer')
            ->where('target_id', $lawyerId)
            ->orderByDesc('acted_at')
            ->get()
            ->map(fn($log) => [
                'id'               => $log->id,
                'action'           => $log->action,
                'previous_status'  => $log->previous_status,
                'new_status'       => $log->new_status,
                'reason'           => $log->reason,
                'admin_identifier' => $log->admin_identifier,
                'acted_at'         => $log->acted_at,
                'meta'             => $log->meta,
            ]);

        return response()->json([
            'success' => true,
            'lawyer'  => ['id' => $lawyer->id, 'full_name' => $lawyer->full_name, 'status' => $lawyer->status],
            'logs'    => $logs,
            'total'   => $logs->count(),
        ]);
    }

    // ── ADMIN: Single lawyer detail ────────────────────────────────
    public function lawyerDetail(Request $request, int $lawyerId)
    {
        $lawyer = Lawyer::withCount(['bids', 'bids as accepted_bids_count' => fn($q) => $q->where('status', 'accepted')])
                        ->findOrFail($lawyerId);

        return response()->json(['success' => true, 'lawyer' => $this->adminLawyerRow($lawyer, true)]);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function adminLawyerRow(Lawyer $l, bool $full = false): array
    {
        $data = [
            'id'               => $l->id,
            'lawyer_code'      => $l->lawyer_code,
            'full_name'        => $l->full_name,
            'email'            => $l->email,
            'phone'            => $l->phone,
            'city'             => $l->city,
            'division'         => $l->division,
            'serving_areas'    => $l->serving_areas ?? [],
            'bar_council_id'   => $l->bar_council_id,
            'bar_council_photo'=> $l->bar_council_photo,
            'profile_photo'    => $l->profile_photo,
            'specializations'  => $l->specializations ?? [],
            'experience_years' => $l->experience_years,
            'min_fee'          => $l->min_fee,
            'bio'              => $l->bio,
            'status'           => $l->status,
            'is_available'     => $l->is_available,
            'rating'           => $l->rating,
            'total_cases'      => $l->total_cases,
            'completed_cases'  => $l->completed_cases,
        ];
        if ($full) {
            $data['total_bids']    = $l->bids_count ?? 0;
            $data['accepted_bids'] = $l->accepted_bids_count ?? 0;
        }
        return $data;
    }

    // ── ADMIN: Warn a lawyer ──────────────────────────────────────
    // ৩টা warning হলে auto-suspend (১ মাস)
    public function warnLawyer(Request $request, int $lawyerId)
    {
        $request->validate(['reason' => 'required|string|min:10|max:500']);

        $lawyer = Lawyer::findOrFail($lawyerId);
        $lawyer->increment('warning_count');

        $newNote = '[' . now()->format('Y-m-d') . "] WARNING #{$lawyer->warning_count}: {$request->reason}";
        $existing = $lawyer->admin_note ? $lawyer->admin_note . "\n" : '';
        $lawyer->update(['admin_note' => $existing . $newNote]);

        $autoSuspended = false;
        if ($lawyer->warning_count >= 3) {
            $lawyer->update([
                'status'          => 'Suspended',
                'is_available'    => false,
                'suspended_until' => now()->addMonths(1),
            ]);
            $lawyer->tokens()->delete();
            $autoSuspended = true;
        }

        AdminActionLog::record(
            targetType:      'lawyer',
            targetId:        $lawyer->id,
            targetName:      $lawyer->full_name,
            action:          'lawyer_warned',
            previousStatus:  $lawyer->status,
            newStatus:       $autoSuspended ? 'Suspended' : $lawyer->status,
            reason:          $request->reason,
            adminIdentifier: $request->session()->get('admin_email') ?? 'admin',
        );

        return response()->json([
            'success'        => true,
            'message'        => $autoSuspended
                ? "3rd warning reached. Lawyer auto-suspended for 1 month."
                : "Warning #{$lawyer->warning_count} issued successfully.",
            'warning_count'  => $lawyer->warning_count,
            'auto_suspended' => $autoSuspended,
        ]);
    }

    // ── Private: OCR — Bar Council card থেকে ID + Name extract ───
    private function runOcr(string $photoPath): array
    {
        try {
            $fullPath = public_path($photoPath);
            if (!file_exists($fullPath)) return ['success' => false];

            exec('which tesseract', $out, $code);
            if ($code !== 0) return ['success' => false, 'reason' => 'tesseract_not_installed'];

            $txtPath = sys_get_temp_dir() . '/bc_' . uniqid();
            exec("tesseract " . escapeshellarg($fullPath) . " " . escapeshellarg($txtPath) . " -l eng 2>/dev/null");
            $text = file_exists($txtPath . '.txt') ? file_get_contents($txtPath . '.txt') : '';
            @unlink($txtPath . '.txt');

            if (empty(trim($text))) return ['success' => false, 'reason' => 'no_text_extracted'];

            // Bar Council ID pattern — e.g. BC/2019/12345 or BCD-2019-12345
            $barId = null;
            if (preg_match('/\b(BCD?[-\/]?\d{4}[-\/]?\d{4,6})\b/i', $text, $m)) {
                $barId = strtoupper(preg_replace('/[^A-Z0-9\-\/]/', '', $m[1]));
            }

            // Name extraction — "Advocate John Doe" or "Name: John Doe"
            $name = null;
            if (preg_match('/(?:Advocate|Adv\.?|Name\s*:)\s+([A-Z][a-zA-Z\s\.]{3,60}?)(?:\n|$)/i', $text, $m)) {
                $name = trim($m[1]);
            }

            return ['success' => true, 'bar_council_id' => $barId, 'name' => $name];
        } catch (\Exception $e) {
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    public function getAuthLawyer(Request $request): ?Lawyer
    {
        try {
            $user = $request->user('lawyer');
            if ($user instanceof Lawyer) return $user;
        } catch (\Exception) {}

        $id = $request->session()->get('lawyer_id') ?? $request->query('lawyer_id');
        return $id ? Lawyer::find($id) : null;
    }

    private function lawyerData(Lawyer $l): array
    {
        return [
            'id'               => $l->id,
            'lawyer_code'      => $l->lawyer_code,
            'full_name'        => $l->full_name,
            'email'            => $l->email,
            'phone'            => $l->phone,
            'bar_council_id'   => $l->bar_council_id,
            'profile_photo'    => $l->profile_photo,
            'city'             => $l->city,
            'division'         => $l->division,
            'serving_areas'    => $l->serving_areas ?? [],
            'specializations'  => $l->specializations ?? [],
            'experience_years' => $l->experience_years,
            'min_fee'          => $l->min_fee,
            'bio'              => $l->bio,
            'status'           => $l->status,
            'is_available'     => $l->is_available,
            'total_cases'      => $l->total_cases,
            'completed_cases'  => $l->completed_cases,
            'rating'           => $l->rating,
            'rating_count'     => $l->rating_count,
            'unread_notifications' => $l->getUnreadNotificationCount(),
        ];
    }
}