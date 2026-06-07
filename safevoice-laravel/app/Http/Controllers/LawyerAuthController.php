<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Lawyer;

class LawyerAuthController extends Controller
{
    // ── POST /api/lawyer/register ──────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'full_name'       => 'required|string|max:100',
            'email'           => 'required|email|unique:lawyers,email',
            'phone'           => 'required|string|unique:lawyers,phone',
            'password'        => 'required|min:8',
            'bar_council_id'  => 'required|string|unique:lawyers,bar_council_id',
            'specializations' => 'nullable|array',
            'experience_years'=> 'nullable|integer|min:0|max:60',
            'city'            => 'nullable|string|max:100',
        ]);

        // ── Photo uploads ──────────────────────────────────────────
        $barCouncilPhoto = null;
        $profilePhoto    = null;

        if ($request->hasFile('bar_council_photo')) {
            $file = $request->file('bar_council_photo');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                return response()->json(['success' => false, 'message' => 'Bar Council card must be JPG, PNG, or PDF.'], 422);
            }
            $filename        = 'bar_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/lawyers'), $filename);
            $barCouncilPhoto = 'uploads/lawyers/' . $filename;
        }

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return response()->json(['success' => false, 'message' => 'Profile photo must be JPG, PNG, or WEBP.'], 422);
            }
            $filename     = 'lp_' . uniqid() . '.' . $ext;
            $file->move(public_path('uploads/lawyers'), $filename);
            $profilePhoto = 'uploads/lawyers/' . $filename;
        }

        $cleanPhone = preg_replace('/\D/', '', $request->phone);
        if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);

        $lawyer = Lawyer::create([
            'lawyer_code'     => Lawyer::generateCode(),
            'full_name'       => $request->full_name,
            'email'           => strtolower(trim($request->email)),
            'email_hash'      => hash('sha256', strtolower(trim($request->email))),
            'phone'           => $cleanPhone,
            'password_hash'   => Hash::make($request->password),
            'bar_council_id'  => strtoupper(trim($request->bar_council_id)),
            'bar_council_photo' => $barCouncilPhoto,
            'profile_photo'   => $profilePhoto,
            'address'         => $request->address ?? null,
            'city'            => $request->city ?? null,
            'specializations' => $request->specializations ?? [],
            'experience_years'=> $request->experience_years ?? 0,
            'min_fee'         => $request->min_fee ?? 500.00,
            'bio'             => $request->bio ?? null,
            'status'          => 'Pending', // admin approve করবে
        ]);

        return response()->json([
            'success' => true,
            'pending' => true,
            'message' => 'Registration successful! Your account is under review. You\'ll be notified within 24-48 hours after admin approval.',
            'lawyer_code' => $lawyer->lawyer_code,
        ]);
    }

    // ── POST /api/lawyer/login ─────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Rate limiting
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

        // Old tokens delete, new token create
        $lawyer->tokens()->delete();
        $token = $lawyer->createToken('lawyer_app')->plainTextToken;

        // Session set (blade view এর জন্য)
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

        $updates = [];

        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', $request->phone);
            if (strlen($phone) === 13) $phone = substr($phone, 2);
            if (Lawyer::where('phone', $phone)->where('id', '!=', $lawyer->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Phone already registered.'], 422);
            }
            $updates['phone'] = $phone;
        }

        if ($request->filled('bio'))             $updates['bio']              = $request->bio;
        if ($request->filled('city'))            $updates['city']             = $request->city;
        if ($request->filled('address'))         $updates['address']          = $request->address;
        if ($request->filled('min_fee'))         $updates['min_fee']          = $request->min_fee;
        if ($request->filled('experience_years'))$updates['experience_years'] = $request->experience_years;
        if ($request->filled('specializations')) $updates['specializations']  = $request->specializations;
        if (isset($request->is_available))       $updates['is_available']     = (bool) $request->is_available;

        // profile photo update
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

    // ── ADMIN: সব lawyers list ─────────────────────────────────────
    public function allLawyers(Request $request)
    {
        $status  = $request->query('status', '');
        $search  = $request->query('search', '');

        $query = Lawyer::query();
        if ($status) $query->where('status', $status);
        if ($search) $query->where(function($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('lawyer_code', 'like', "%$search%")
              ->orWhere('bar_council_id', 'like', "%$search%");
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
        $request->validate(['action' => 'required|in:approve,reject']);

        $lawyer = Lawyer::findOrFail($lawyerId);
        $newStatus = $request->action === 'approve' ? 'Active' : 'Banned';
        $lawyer->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Lawyer {$request->action}d successfully.",
            'status'  => $newStatus,
        ]);
    }

    // ── ADMIN: Suspend / Unsuspend a lawyer ────────────────────────
    public function toggleSuspend(Request $request, int $lawyerId)
    {
        $lawyer = Lawyer::findOrFail($lawyerId);

        if ($lawyer->status === 'Suspended') {
            $lawyer->update(['status' => 'Active']);
            $msg = 'Lawyer unsuspended.';
            $newStatus = 'Active';
        } else {
            $lawyer->update(['status' => 'Suspended', 'is_available' => false]);
            $msg = 'Lawyer suspended.';
            $newStatus = 'Suspended';
        }

        return response()->json(['success' => true, 'message' => $msg, 'status' => $newStatus]);
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
            'id'              => $l->id,
            'lawyer_code'     => $l->lawyer_code,
            'full_name'       => $l->full_name,
            'email'           => $l->email,
            'phone'           => $l->phone,
            'city'            => $l->city,
            'bar_council_id'  => $l->bar_council_id,
            'bar_council_photo'=> $l->bar_council_photo,
            'profile_photo'   => $l->profile_photo,
            'specializations' => $l->specializations ?? [],
            'experience_years'=> $l->experience_years,
            'min_fee'         => $l->min_fee,
            'bio'             => $l->bio,
            'status'          => $l->status,
            'is_available'    => $l->is_available,
            'rating'          => $l->rating,
            'total_cases'     => $l->total_cases,
            'completed_cases' => $l->completed_cases,
        ];
        if ($full) {
            $data['total_bids']    = $l->bids_count ?? 0;
            $data['accepted_bids'] = $l->accepted_bids_count ?? 0;
        }
        return $data;
    }

    private function getAuthLawyer(Request $request): ?Lawyer
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
