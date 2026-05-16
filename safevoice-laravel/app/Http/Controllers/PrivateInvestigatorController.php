<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivateInvestigator;
use App\Models\Complaint;
use App\Models\PiNotification;
use App\Models\PiPayment;

class PrivateInvestigatorController extends Controller
{
    // GET /api/pi
    public function index()
    {
        $pis = PrivateInvestigator::orderBy('pi_code')->get();
        return response()->json(['success' => true, 'investigators' => $pis]);
    }

    // POST /api/pi/assign
    public function assign(Request $request)
    {
        $request->validate([
            'complaint_id' => 'required|string',
            'pi_id'        => 'required|integer',
        ]);

        $complaint = Complaint::where('complaint_id', $request->complaint_id)->first();
        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found'], 404);
        }

        $pi = PrivateInvestigator::find($request->pi_id);
        if (!$pi) {
            return response()->json(['success' => false, 'message' => 'PI not found'], 404);
        }

        $complaint->update([
            'assigned_pi_id' => $pi->id,
            'pi_assigned_at' => now(),
            'status'         => 'Private Investigator Assigned',
        ]);

        $pi->increment('active_cases');
        $pi->increment('total_cases');

        return response()->json(['success' => true, 'message' => 'PI assigned successfully']);
    }

    // GET /api/pi/notifications
    public function notifications(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notifications = PiNotification::where('user_id', $request->session()->get('user_id'))
            ->orderByDesc('sent_at')
            ->get();

        return response()->json(['success' => true, 'notifications' => $notifications]);
    }

    // POST /api/pi/payment
    public function payment(Request $request)
    {
        $request->validate([
            'complaint_id'   => 'required|string',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank',
            'txn_id'         => 'required|string|unique:pi_payments,txn_id',
        ]);

        $payment = PiPayment::create([
            'complaint_id'   => $request->complaint_id,
            'user_id'        => $request->session()->get('user_id'),
            'payment_method' => $request->payment_method,
            'sender_number'  => $request->sender_number,
            'txn_id'         => $request->txn_id,
            'status'         => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Payment submitted, awaiting confirmation']);
    }
}
