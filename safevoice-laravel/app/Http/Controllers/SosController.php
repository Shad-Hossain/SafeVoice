<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SosAlert;
use App\Models\SosNotification;
use App\Models\SosResponder;
use App\Models\User;

class SosController extends Controller
{
    // POST /api/sos/create
    public function create(Request $request)
    {
        $userId = $request->session()->get('user_id', 1);

        $sos = SosAlert::create([
            'user_id'       => $userId,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'location_text' => $request->location,
            'crime_type'    => $request->crime_type,
            'description'   => $request->description,
            'status'        => 'active',
        ]);

        return response()->json(['success' => true, 'sos_id' => $sos->id]);
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
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
