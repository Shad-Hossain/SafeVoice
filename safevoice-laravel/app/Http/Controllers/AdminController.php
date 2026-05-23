<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Complaint;
use App\Models\SosResponder;

class AdminController extends Controller
{
    protected string $adminEmail    = 'admin@safevoice.com';
    protected string $adminPassHash = '$2y$12$oeCj3khhTjHhTR8N/F1XR.vUcIfdmX3wFLMeiJGzCEmF1mXPyGtTm';

    // POST /api/admin/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($request->email !== $this->adminEmail ||
            !Hash::check($request->password, $this->adminPassHash)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_id',    1);
        $request->session()->put('admin_email', $this->adminEmail);
        $request->session()->put('is_admin',    true);

        return response()->json(['success' => true, 'message' => 'Login successful']);
    }

    // POST /api/admin/logout
    public function logout(Request $request)
    {
        $request->session()->flush();
        return response()->json(['success' => true]);
    }

    // GET /api/admin/users
    public function users()
    {
        $users = User::orderByDesc('joined_at')->get();
        return response()->json(['success' => true, 'users' => $users]);
    }

    // POST /api/admin/users/update-status
    public function updateUserStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'status' => 'required|in:Active,Suspended,Probation,Banned',
        ]);

        User::where('id', $request->id)->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => 'User status updated to ' . $request->status]);
    }

    // ── GET /api/admin/pending-accounts ──────────────────────────
    // Birth certificate দিয়ে register করা pending users এর list
    public function pendingAccounts()
    {
        $users = User::where('status', 'Pending')
            ->where('id_type', 'birth_certificate')
            ->orderByDesc('joined_at')
            ->get([
                'id', 'name', 'email', 'phone',
                'id_number', 'id_document_path', 'joined_at'
            ]);

        return response()->json([
            'success' => true,
            'users'   => $users,
            'count'   => $users->count(),
        ]);
    }

    // ── POST /api/admin/approve-account ──────────────────────────
    public function approveAccount(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $user = User::where('id', $request->id)
            ->where('status', 'Pending')
            ->firstOrFail();

        $user->update([
            'status'           => 'Active',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Account for {$user->name} approved successfully.",
        ]);
    }

    // ── POST /api/admin/reject-account ───────────────────────────
    public function rejectAccount(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::where('id', $request->id)
            ->where('status', 'Pending')
            ->firstOrFail();

        // Rejected user কে delete করো — ওরা আবার register করতে পারবে
        $userName = $user->name;

        // Document file delete করো
        if ($user->id_document_path && file_exists(public_path($user->id_document_path))) {
            unlink(public_path($user->id_document_path));
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Account request for {$userName} has been rejected and removed.",
        ]);
    }

    // GET /api/stats — home page public stats
    public function publicStats()
    {
        $total    = DB::table('complaints')->count();
        $resolved = DB::table('complaints')->where('status', 'resolved')->count();
        $pending  = DB::table('complaints')->whereIn('status', ['submitted','under_review','pending'])->count();
        $sos      = DB::table('sos_responders')->count();

        return response()->json([
            'success'  => true,
            'total'    => $total,
            'resolved' => $resolved,
            'pending'  => $pending,
            'sos'      => $sos,
        ]);
    }
}