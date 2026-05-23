<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Complaint;
use App\Models\PrivateInvestigator;
use App\Models\SuperAdminNotification;

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

    // GET /api/super-admin/pi-cases — kon PI kon case peyeche
    public function piCases()
    {
        $pis = PrivateInvestigator::with([])->orderBy('pi_code')->get();

        $result = $pis->map(function($pi) {
            $cases = Complaint::where('assigned_pi_id', $pi->id)
                ->orderByDesc('pi_assigned_at')
                ->get(['complaint_id','type','location','status','pi_assigned_at','is_anonymous']);

            return [
                'pi_code'      => $pi->pi_code,
                'full_name'    => $pi->full_name,
                'email'        => $pi->email,
                'phone'        => $pi->phone,
                'is_active'    => $pi->is_active,
                'active_cases' => $pi->active_cases,
                'total_cases'  => $pi->total_cases,
                'cases'        => $cases,
            ];
        });

        return response()->json(['success' => true, 'pi_list' => $result]);
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

    // ── Notification endpoints ────────────────────────────────────

    // GET /api/super-admin/notifications
    public function notifications()
    {
        $notifications = SuperAdminNotification::orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
            'unread_count'  => SuperAdminNotification::unreadCount(),
        ]);
    }

    // GET /api/super-admin/notifications/unread-count
    public function notificationsUnreadCount()
    {
        return response()->json([
            'success'      => true,
            'unread_count' => SuperAdminNotification::unreadCount(),
        ]);
    }

    // POST /api/super-admin/notifications/mark-read
    public function notificationsMarkRead(Request $request)
    {
        $id = $request->input('id');
        if ($id) {
            SuperAdminNotification::where('id', $id)->update(['is_read' => true]);
        } else {
            SuperAdminNotification::where('is_read', false)->update(['is_read' => true]);
        }
        return response()->json(['success' => true, 'unread_count' => SuperAdminNotification::unreadCount()]);
    }

    // ── Refund endpoints ──────────────────────────────────────────

    // GET /api/super-admin/refunds
    public function refunds()
    {
        $payments = \App\Models\PiPayment::where('status', 'refunded')
            ->orderByRaw("CASE WHEN refund_status IS NULL OR refund_status != 'processed' THEN 0 ELSE 1 END")
            ->orderByDesc('initiated_at')
            ->get();

        $result = $payments->map(function ($p) {
            $complaint = \App\Models\Complaint::where('complaint_id', $p->complaint_id)
                ->first(['complaint_id', 'type', 'status', 'submitted_at']);
            $user = $p->user_id ? \App\Models\User::find($p->user_id, ['name', 'email']) : null;

            return [
                'id'             => $p->id,
                'complaint_id'   => $p->complaint_id,
                'type'           => $complaint?->type ?? '—',
                'amount'         => $p->amount,
                'payment_method' => $p->payment_method,
                'sender_number'  => $p->sender_number,
                'txn_id'         => $p->txn_id,
                'status'         => $p->status,
                'refund_status'  => $p->refund_status ?? null,
                'confirmed_at'   => $p->confirmed_at,
                'failed_at'      => $p->updated_at,
                'processed_at'   => $p->refund_processed_at ?? null,
                'user_name'      => $user?->name  ?? 'Anonymous',
                'user_email'     => $user?->email ?? '—',
            ];
        });

        return response()->json(['success' => true, 'refunds' => $result]);
    }

    // GET /api/super-admin/refunds/pending-count
    public function refundsPendingCount()
    {
        $count = \App\Models\PiPayment::where('status', 'refunded')
            ->where(function ($q) {
                $q->whereNull('refund_status')
                  ->orWhere('refund_status', '!=', 'processed');
            })->count();

        return response()->json(['success' => true, 'pending_count' => $count]);
    }

    // POST /api/super-admin/refunds/mark-processed
    public function markRefundProcessed(Request $request)
    {
        $request->validate(['payment_id' => 'required|integer']);

        $payment = \App\Models\PiPayment::find($request->payment_id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $payment->update([
            'refund_status'       => 'processed',
            'refund_processed_at' => now(),
        ]);

        // User কে in-app notification পাঠাও
        if ($payment->user_id) {
            \App\Models\UserNotification::notify(
                $payment->user_id,
                'refund_initiated',
                '✅ Refund Sent!',
                "তোমার complaint {$payment->complaint_id} এর জন্য ৳{$payment->amount} refund পাঠানো হয়েছে। " .
                strtoupper($payment->payment_method) . " নম্বর {$payment->sender_number} এ check করো।",
                [
                    'complaint_id' => $payment->complaint_id,
                    'action_url'   => '/dashboard',
                    'icon'         => '💰',
                ]
            );
        }

        // Super admin notification mark read করো (same complaint)
        \App\Models\SuperAdminNotification::where('complaint_id', $payment->complaint_id)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Refund marked as processed. User notified.']);
    }
}