<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|unique:users,phone',
            'password'  => 'required|min:8',
            'id_type'   => 'required|in:nid,birth_certificate',
            'id_number' => 'required|string',
        ]);

        $cleanPhone = preg_replace('/\D/', '', $request->phone);
        if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);

        $idDocPath    = null;
        $profilePhoto = null;

        if ($request->hasFile('id_document')) {
            $file      = $request->file('id_document');
            $filename  = 'id_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $idDocPath = 'uploads/' . $filename;
        }

        if ($request->hasFile('profile_photo')) {
            $file      = $request->file('profile_photo');
            $filename  = 'photo_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $profilePhoto = 'uploads/' . $filename;
        }

        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'phone'            => $cleanPhone,
            'password_hash'    => Hash::make($request->password),
            'id_type'          => $request->id_type,
            'id_number'        => $request->id_number,
            'id_document_path' => $idDocPath,
            'location'         => $request->location ?? '',
            'profile_photo'    => $profilePhoto,
        ]);

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful!',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        if ($user->status === 'Banned') {
            return response()->json(['success' => false, 'message' => 'Your account has been banned.'], 403);
        }

        if ($user->status === 'Suspended') {
            return response()->json(['success' => false, 'message' => 'Your account is suspended.'], 403);
        }

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        // Session-এ user info রাখো — API calls-এ লাগবে
        $request->session()->put('user_id',    $user->id);
        $request->session()->put('user_name',  $user->name);
        $request->session()->put('user_email', $user->email);

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'token'   => $token,
            'user'    => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'status'           => $user->status,
                'profile_photo'    => $user->profile_photo,
                'complaints_count' => $user->complaints_count,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->session()->flush();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    // GET /api/check-session
    public function checkSession(Request $request)
    {
        if ($request->session()->has('user_id')) {
            return response()->json([
                'success'  => true,
                'loggedIn' => true,
                'user'     => [
                    'id'    => $request->session()->get('user_id'),
                    'name'  => $request->session()->get('user_name'),
                    'email' => $request->session()->get('user_email'),
                ],
            ]);
        }
        return response()->json(['success' => false, 'loggedIn' => false], 401);
    }
}