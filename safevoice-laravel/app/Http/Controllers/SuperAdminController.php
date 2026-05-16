<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Complaint;

class SuperAdminController extends Controller
{
    // POST /api/super-admin/login  (also /api/super_admin_auth)
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $admin = SuperAdmin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin_id',       $admin->id);
        $request->session()->put('super_admin_username', $admin->username);
        $request->session()->put('is_super_admin',       true);

        return response()->json(['success' => true, 'message' => 'Login successful']);
    }

    // POST /api/super-admin/logout
    public function logout(Request $request)
    {
        $request->session()->flush();
        return response()->json(['success' => true]);
    }

    // GET /api/super-admin/stats
    public function stats()
    {
        return response()->json([
            'success'    => true,
            'users'      => User::count(),
            'complaints' => Complaint::count(),
            'resolved'   => Complaint::where('status', 'Resolved')->count(),
            'pending'    => Complaint::whereIn('status', ['Submitted', 'Under Review'])->count(),
        ]);
    }

    // GET /api/super-admin/users
    public function users()
    {
        $users = User::orderByDesc('joined_at')->get();
        return response()->json(['success' => true, 'users' => $users]);
    }

    // GET /api/super-admin/complaints
    public function complaints()
    {
        $complaints = Complaint::orderByDesc('submitted_at')->get();
        return response()->json(['success' => true, 'complaints' => $complaints]);
    }

    // POST /api/super-admin/update-status
    public function updateUserStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'status' => 'required|in:Active,Suspended,Probation,Banned',
        ]);

        User::where('id', $request->id)->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => 'User status updated to ' . $request->status]);
    }
}
