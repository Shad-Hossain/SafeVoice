<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
}
