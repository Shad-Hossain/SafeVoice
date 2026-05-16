<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SosAlert;
use App\Models\SosNotification;
use App\Models\SosResponder;
use App\Models\User;

class SosController extends Controller
{

public function notify(Request $request)
{
    $sosId = $request->sos_id;
    $lat   = $request->latitude;
    $lng   = $request->longitude;

    if ($sosId) {
        SosAlert::where('id', $sosId)->update([
            'latitude'      => $lat,
            'longitude'     => $lng,
            'location_text' => $request->location,
        ]);
    }

    $notifiedCount = 0;
    $users = User::where('id', '!=', $request->session()->get('user_id', 0))->get();
    foreach ($users as $u) {
        SosNotification::firstOrCreate([
            'sos_id'           => $sosId,
            'notified_user_id' => $u->id,
        ], ['status' => 'sent']);
        $notifiedCount++;
    }

    return response()->json([
        'success'        => true,
        'notified_count' => $notifiedCount,
    ]);
}
    // POST /api/sos/create
    public function create(Request $request)
    {
        // Session থেকে user_id নাও; login না থাকলে default 0 (anonymous SOS)
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id')
                ?? 0;

        try {
            $sos = SosAlert::create([
                'user_id'       => $userId ?: null,
                'latitude'      => $request->latitude,
                'longitude'     => $request->longitude,
                'location_text' => $request->location,
                'crime_type'    => $request->crime_type,
                'description'   => $request->description,
                'status'        => 'active',
            ]);
            return response()->json(['success' => true, 'sos_id' => $sos->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/sos/alerts
    public function alerts()
    {
        $alerts = SosAlert::with('user')->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'alerts' => $alerts]);
    }

    // GET /api/sos/my-notifications
    public function myNotifications(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            // Login না থাকলে empty return — 401 দিলে JS console-এ error spam হয়
            return response()->json(['success' => true, 'notifications' => []]);
        }

        $notifications = SosNotification::where('notified_user_id', $request->session()->get('user_id'))
            ->with('sosAlert')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'notifications' => $notifications]);
    }

    // POST /api/sos/respond
    public function respond(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $request->session()->get('user_id');
        $sosId  = $request->sos_id;

        SosResponder::firstOrCreate([
            'sos_id'       => $sosId,
            'responder_id' => $userId,
        ]);

        SosNotification::where('sos_id', $sosId)
            ->where('notified_user_id', $userId)
            ->update(['status' => 'responded']);

        return response()->json(['success' => true, 'message' => 'Response recorded']);
    }
}