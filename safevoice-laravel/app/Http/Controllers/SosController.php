<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SosAlert;
use App\Models\SosNotification;
use App\Models\SosResponder;
use App\Models\User;
use App\Http\Controllers\FcmController;

class SosController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // POST /api/user/update-location
    // Frontend থেকে প্রতি 30 সেকেন্ডে user এর live location update
    // ─────────────────────────────────────────────────────────
    public function updateLocation(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false], 401);
        }

        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        User::where('id', $userId)->update([
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'last_seen' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/sos/notify
    // Smart radius — 1km → 2km → 3km → 7km
    // শুধু nearby active users কে notify করবে
    // ─────────────────────────────────────────────────────────
    public function notify(Request $request)
    {
        $sosId = $request->sos_id;
        $lat   = (float) $request->latitude;
        $lng   = (float) $request->longitude;

        // SOS alert location update করো
        if ($sosId) {
            SosAlert::where('id', $sosId)->update([
                'latitude'      => $lat,
                'longitude'     => $lng,
                'location_text' => $request->location,
            ]);
        }

        $alerterId   = $request->session()->get('user_id', 0) ?: (int)$request->input('user_id', 0);
        $alerter     = $alerterId ? User::find($alerterId) : null;
        $alerterName = $alerter ? $alerter->name : ($request->input('contact_name') ?: 'Someone');
        $location    = $request->location ?: 'Unknown location';

        // Smart radius — 1 → 2 → 3 → 7 km পর্যন্ত খুঁজবে
        $radii       = [1, 2, 3, 7];
        $nearbyUsers = collect();

        foreach ($radii as $km) {
            $nearbyUsers = $this->getUsersWithinRadius($lat, $lng, $km, $alerterId);
            if ($nearbyUsers->isNotEmpty()) {
                Log::info("SOS #{$sosId}: Found {$nearbyUsers->count()} users within {$km}km");
                break;
            }
            Log::info("SOS #{$sosId}: No users within {$km}km, expanding...");
        }

        $notifiedCount = 0;

        if ($nearbyUsers->isNotEmpty()) {
            $userIds = $nearbyUsers->pluck('id')->toArray();
            FcmController::sendToUsers(
                $userIds,
                '🚨 Emergency SOS Alert!',
                "{$alerterName} needs help near {$location}. Tap to respond.",
                ['type' => 'sos', 'sos_id' => (string)$sosId, 'url' => '/sos']
            );

            foreach ($nearbyUsers as $u) {
                SosNotification::firstOrCreate([
                    'sos_id'           => $sosId,
                    'notified_user_id' => $u->id,
                ], ['status' => 'sent']);
                $notifiedCount++;
            }
        }

        return response()->json([
            'success'        => true,
            'notified_count' => $notifiedCount,
            'message'        => $notifiedCount > 0
                ? "Notified {$notifiedCount} nearby users."
                : 'No nearby users found within 7km.',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Haversine formula দিয়ে radius এর মধ্যে active users খোঁজো
    //
    // INNER JOIN fcm_tokens  → শুধু যাদের FCM token আছে তারা
    // LEFT JOIN sos_responders → কে আগে respond করেছে সেটা জানতে
    // Haversine distance_km → সবচেয়ে কাছের user আগে (ORDER BY ASC)
    // Fallback (7km) → সব FCM users কে notify
    // ─────────────────────────────────────────────────────────
    private function getUsersWithinRadius(float $lat, float $lng, int $km, int $excludeId)
    {
        // ── Primary Query ──────────────────────────────────────
        // users INNER JOIN fcm_tokens  (FCM token থাকতেই হবে)
        // LEFT JOIN sos_responders     (আগে কতবার respond করেছে — experience দেখতে)
        // Haversine দিয়ে distance_km calculate, radius এর মধ্যে filter
        // ORDER BY distance_km ASC — সবচেয়ে কাছের user সবার আগে
        $users = DB::select("
            SELECT
                u.id,
                u.name,
                u.latitude,
                u.longitude,
                ft.token                        AS fcm_token,
                COUNT(sr.id)                    AS total_responds,
                ROUND(
                    (6371 * ACOS(
                        COS(RADIANS(:lat1)) *
                        COS(RADIANS(u.latitude)) *
                        COS(RADIANS(u.longitude) - RADIANS(:lng1)) +
                        SIN(RADIANS(:lat2)) *
                        SIN(RADIANS(u.latitude))
                    )), 4
                )                               AS distance_km
            FROM users u
            INNER JOIN fcm_tokens ft
                ON ft.user_id = u.id
            LEFT JOIN sos_responders sr
                ON sr.responder_id = u.id
            WHERE u.id        != :excludeId
              AND u.latitude  IS NOT NULL
              AND u.longitude IS NOT NULL
              AND (
                    6371 * ACOS(
                        COS(RADIANS(:lat3)) *
                        COS(RADIANS(u.latitude)) *
                        COS(RADIANS(u.longitude) - RADIANS(:lng2)) +
                        SIN(RADIANS(:lat4)) *
                        SIN(RADIANS(u.latitude))
                    )
                  ) <= :km
            GROUP BY u.id, u.name, u.latitude, u.longitude, ft.token
            ORDER BY distance_km ASC
        ", [
            'lat1'      => $lat,
            'lng1'      => $lng,
            'lat2'      => $lat,
            'lat3'      => $lat,
            'lng2'      => $lng,
            'lat4'      => $lat,
            'excludeId' => $excludeId,
            'km'        => $km,
        ]);

        if (!empty($users)) {
            return collect($users);
        }

        // ── Fallback Query (শুধু 7km এ) ───────────────────────
        // Location নেই কিন্তু FCM token আছে — এমন সব users কে notify
        // users INNER JOIN fcm_tokens
        // LEFT JOIN sos_responders (experience দেখতে)
        if ($km >= 7) {
            $fallback = DB::select("
                SELECT
                    u.id,
                    u.name,
                    u.latitude,
                    u.longitude,
                    ft.token        AS fcm_token,
                    COUNT(sr.id)    AS total_responds,
                    NULL            AS distance_km
                FROM users u
                INNER JOIN fcm_tokens ft
                    ON ft.user_id = u.id
                LEFT JOIN sos_responders sr
                    ON sr.responder_id = u.id
                WHERE u.id != :excludeId
                GROUP BY u.id, u.name, u.latitude, u.longitude, ft.token
                ORDER BY total_responds DESC
            ", ['excludeId' => $excludeId]);

            return collect($fallback);
        }

        return collect();
    }

    // POST /api/sos/create
    public function create(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id')
                ?? 0;

        try {
            $sos = new SosAlert();
            $sos->user_id       = $userId ?: null;
            $sos->latitude      = $request->latitude;
            $sos->longitude     = $request->longitude;
            $sos->location_text = $request->location;
            $sos->crime_type    = $request->crime_type;
            $sos->description   = $request->description;
            $sos->status        = 'active';
            $sos->contact_phone = $request->input('contact_phone');
            $sos->contact_name  = $request->input('contact_name');
            $sos->save();
            return response()->json(['success' => true, 'sos_id' => $sos->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/sos/alerts
    public function alerts(Request $request)
    {
        $sosId = $request->query('sos_id');

        if ($sosId) {
            $alert = SosAlert::with(['user', 'evidence', 'responders'])->find($sosId);
            if (!$alert) {
                return response()->json(['success' => false, 'message' => 'SOS not found'], 404);
            }
            $currentUserId = (int) request()->session()->get('user_id')
                          ?: (int) request()->query('user_id')
                          ?: 0;
            $responderIds = $alert->responders ? $alert->responders->pluck('responder_id')->toArray() : [];

            $sosData = [
                'id'              => $alert->id,
                'victim_name'     => $alert->user ? $alert->user->name : ($alert->contact_name ?: 'Anonymous'),
                'victim_phone'    => $alert->user ? $alert->user->phone : $alert->contact_phone,
                'location_text'   => $alert->location_text,
                'crime_type'      => $alert->crime_type,
                'description'     => $alert->description,
                'latitude'        => $alert->latitude,
                'longitude'       => $alert->longitude,
                'created_at'      => $alert->created_at,
                'status'          => $alert->status,
                'i_responded'     => in_array((int)$currentUserId, $responderIds),
                'responder_count' => count($responderIds),
            ];
            $evidence = $alert->evidence ?? [];
            return response()->json(['success' => true, 'sos' => $sosData, 'evidence' => $evidence]);
        }

        $alerts = SosAlert::with('user')->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'alerts' => $alerts]);
    }

    // GET /api/sos/my-notifications
    public function myNotifications(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id')
                ?? null;

        if (!$userId) {
            return response()->json(['success' => true, 'notifications' => []]);
        }

        $notifications = SosNotification::where('notified_user_id', $userId)
            ->with(['sosAlert.user'])
            ->orderByDesc('created_at')
            ->get();

        $mapped = $notifications->map(function ($n) {
            $alert = $n->sosAlert;
            return [
                'id'            => $n->id,
                'sos_id'        => $n->sos_id,
                'status'        => $n->status,
                'created_at'    => $n->created_at,
                'victim_name'   => $alert && $alert->user ? $alert->user->name : 'Unknown',
                'location_text' => $alert ? $alert->location_text : null,
                'crime_type'    => $alert ? $alert->crime_type    : null,
                'sos_time'      => $alert ? $alert->created_at    : null,
                'description'   => $alert ? $alert->description   : null,
                'latitude'      => $alert ? $alert->latitude      : null,
                'longitude'     => $alert ? $alert->longitude     : null,
                'sos_status'    => $alert ? $alert->status        : null,
            ];
        });

        return response()->json(['success' => true, 'notifications' => $mapped]);
    }

    // POST /api/sos/respond
    public function respond(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $sosId = $request->sos_id;

        SosResponder::firstOrCreate([
            'sos_id'       => $sosId,
            'responder_id' => $userId,
        ]);

        SosNotification::where('sos_id', $sosId)
            ->where('notified_user_id', $userId)
            ->update(['status' => 'responded']);

        return response()->json(['success' => true, 'message' => 'Response recorded']);
    }

    // GET /api/sos/my-responds
    public function myResponds(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $responds = SosResponder::where('responder_id', $userId)
            ->with(['sos' => function($q) {
                $q->with('user:id,name,phone');
            }])
            ->orderByDesc('responded_at')
            ->get();

        $mapped = $responds->map(function ($r) {
            $sos = $r->sos;
            return [
                'responder_record_id'   => $r->id,
                'sos_id'                => $r->sos_id,
                'responded_at'          => $r->responded_at,
                'evidence_path'         => $r->evidence_path,
                'file_type'             => $r->file_type,
                'evidence_status'       => $r->evidence_status ?? 'not_submitted',
                'evidence_submitted_at' => $r->evidence_submitted_at,
                'admin_note'            => $r->admin_note,
                'verified_at'           => $r->verified_at,
                'victim_name'           => $sos && $sos->user ? $sos->user->name : 'Anonymous',
                'location_text'         => $sos ? $sos->location_text : null,
                'crime_type'            => $sos ? $sos->crime_type    : null,
                'description'           => $sos ? $sos->description   : null,
                'sos_status'            => $sos ? $sos->status        : null,
                'sos_created_at'        => $sos ? $sos->created_at    : null,
            ];
        });

        return response()->json(['success' => true, 'responds' => $mapped]);
    }

    // GET /api/sos/victim-evidence?sos_id=123
    public function victimEvidence(Request $request)
    {
        $sosId = $request->query('sos_id');
        if (!$sosId) {
            return response()->json(['success' => false, 'message' => 'sos_id required'], 422);
        }

        $alert = SosAlert::with(['user:id,name,phone', 'evidence'])->find($sosId);
        if (!$alert) {
            return response()->json(['success' => false, 'message' => 'SOS not found'], 404);
        }

        return response()->json([
            'success' => true,
            'sos' => [
                'id'            => $alert->id,
                'victim_name'   => $alert->user ? $alert->user->name  : 'Anonymous',
                'victim_phone'  => $alert->user ? $alert->user->phone : null,
                'location_text' => $alert->location_text,
                'crime_type'    => $alert->crime_type,
                'description'   => $alert->description,
                'status'        => $alert->status,
                'created_at'    => $alert->created_at,
            ],
            'evidence' => $alert->evidence ?? [],
        ]);
    }

    // POST /api/sos/submit-responder-evidence
    public function submitResponderEvidence(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'sos_id'   => 'required|integer',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:51200',
        ]);

        $sosId = $request->sos_id;

        /** @var SosResponder $responder */
        $responder = SosResponder::where('sos_id', $sosId)
            ->where('responder_id', $userId)
            ->first();

        if (!$responder) {
            return response()->json(['success' => false, 'message' => 'You have not responded to this SOS'], 403);
        }

        if ($responder->evidence_status === 'approved') {
            return response()->json(['success' => false, 'message' => 'Evidence already approved'], 422);
        }

        $file     = $request->file('evidence');
        $ext      = $file->getClientOriginalExtension();
        $fileType = in_array(strtolower($ext), ['jpg','jpeg','png']) ? 'image' : 'video';
        $fileName = 'sos_resp_' . $sosId . '_' . $userId . '_' . time() . '.' . $ext;

        if (!file_exists(public_path('uploads/sos_evidence'))) {
            mkdir(public_path('uploads/sos_evidence'), 0755, true);
        }

        $file->move(public_path('uploads/sos_evidence'), $fileName);
        $filePath = 'uploads/sos_evidence/' . $fileName;

        $responder->update([
            'evidence_path'         => $filePath,
            'file_type'             => $fileType,
            'evidence_status'       => 'pending',
            'evidence_submitted_at' => now(),
            'admin_note'            => null,
            'verified_at'           => null,
        ]);

        return response()->json([
            'success'        => true,
            'message'        => 'Evidence submitted successfully. Admin will review and approve.',
            'evidence_path'  => $filePath,
        ]);
    }

    // POST /api/sos/upload-evidence
    public function uploadEvidence(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'sos_id'   => 'required|integer',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:51200',
        ]);

        $sosId = $request->sos_id;

        /** @var SosResponder $responder */
        $responder = SosResponder::where('sos_id', $sosId)
            ->where('responder_id', $userId)
            ->first();

        if (!$responder) {
            return response()->json(['success' => false, 'message' => 'You have not responded to this SOS']);
        }

        $file     = $request->file('evidence');
        $ext      = $file->getClientOriginalExtension();
        $fileType = in_array(strtolower($ext), ['jpg','jpeg','png']) ? 'image' : 'video';
        $fileName = 'sos_ev_' . $sosId . '_' . $userId . '_' . time() . '.' . $ext;
        $file->move(public_path('uploads/sos_evidence'), $fileName);
        $filePath = 'uploads/sos_evidence/' . $fileName;

        $responder->update([
            'evidence_path'         => $filePath,
            'file_type'             => $fileType,
            'evidence_status'       => 'pending',
            'evidence_submitted_at' => now(),
        ]);

        User::where('id', $userId)->increment('sos_helped_count');

        return response()->json(['success' => true, 'message' => 'Evidence uploaded. Waiting for admin verification.']);
    }

    // GET /api/admin/sos-evidence-pending
    public function adminPendingEvidence(Request $request)
    {
        $pending = SosResponder::with([
            'sos:id,location_text,crime_type,created_at,user_id',
            'sos.user:id,name,phone',
            'responder:id,name,phone,sos_helped_verified_count',
        ])
            ->orderByDesc('evidence_submitted_at')
            ->get();

        return response()->json(['success' => true, 'pending' => $pending]);
    }

    // POST /api/admin/sos-evidence-verify
    public function adminVerifyEvidence(Request $request)
    {
        $request->validate([
            'responder_id' => 'required|integer',
            'action'       => 'required|in:approve,reject',
            'note'         => 'nullable|string|max:500',
        ]);

        /** @var SosResponder $responder */
        $responder = SosResponder::find($request->responder_id);
        if (!$responder) {
            return response()->json(['success' => false, 'message' => 'Record not found'], 404);
        }

        if ($request->action === 'approve') {
            $responder->update([
                'evidence_status' => 'approved',
                'admin_note'      => $request->note,
                'verified_at'     => now(),
            ]);
            User::where('id', $responder->responder_id)->increment('sos_helped_verified_count');
            $message = 'Evidence approved. User ranking updated.';
        } else {
            $responder->update([
                'evidence_status' => 'rejected',
                'admin_note'      => $request->note,
                'verified_at'     => now(),
            ]);
            User::where('id', $responder->responder_id)
                ->where('sos_helped_count', '>', 0)
                ->decrement('sos_helped_count');
            $message = 'Evidence rejected.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    // GET /api/leaderboard
    public function leaderboard(Request $request)
    {
        $loggedInUserId = $request->session()->get('user_id');

        $users = User::where('sos_helped_verified_count', '>', 0)
            ->orderByDesc('sos_helped_verified_count')
            ->orderByDesc('sos_helped_count')
            ->limit(50)
            ->get(['id', 'name', 'sos_helped_verified_count', 'sos_helped_count']);

        $leaderboard = [];
        foreach ($users as $index => $user) {
            $rank = $index + 1;
            $leaderboard[] = [
                'rank'      => $rank,
                'name'      => $user->name,
                'responses' => $user->sos_helped_verified_count,
                'badge'     => $this->getBadge($rank),
                'is_you'    => ($loggedInUserId && $user->id == $loggedInUserId),
            ];
        }

        $myRank = null;
        if ($loggedInUserId) {
            $me = User::find($loggedInUserId);
            if ($me) {
                $position = User::where('sos_helped_verified_count', '>', $me->sos_helped_verified_count)->count() + 1;
                $myRank = [
                    'rank'      => $position,
                    'name'      => $me->name,
                    'responses' => $me->sos_helped_verified_count,
                ];
            }
        }

        return response()->json([
            'success'     => true,
            'leaderboard' => $leaderboard,
            'my_rank'     => $myRank,
            'total_users' => User::where('sos_helped_verified_count', '>', 0)->count(),
        ]);
    }

    // GET /api/leaderboard/search
    public function leaderboardSearch(Request $request)
    {
        $idNumber = trim($request->query('id_number', ''));
        if (!$idNumber) {
            return response()->json(['success' => false, 'message' => 'ID number required'], 422);
        }

        $user = User::where('id_number', $idNumber)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No user found with this ID number']);
        }

        $position = User::where('sos_helped_verified_count', '>', $user->sos_helped_verified_count)->count() + 1;

        return response()->json([
            'success' => true,
            'result'  => [
                'name'      => $user->name,
                'rank'      => $position,
                'responses' => $user->sos_helped_verified_count,
                'badge'     => $this->getBadge($position),
            ],
        ]);
    }

    private function getBadge(int $rank): string
    {
        if ($rank === 1) return '🏆 Champion';
        if ($rank === 2) return '🥈 Runner Up';
        if ($rank === 3) return '🥉 Third Place';
        if ($rank <= 10) return '⭐ Top Responder';
        return '🎖️ Active';
    }

    // GET /api/sos/all-requests
    public function allSosRequests(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $alerts = SosAlert::with(['user', 'responders'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($a) use ($userId) {
                $responderIds = $a->responders ? $a->responders->pluck('responder_id')->toArray() : [];
                return [
                    'id'              => $a->id,
                    'victim_name'     => $a->user ? $a->user->name : 'Anonymous',
                    'location_text'   => $a->location_text,
                    'crime_type'      => $a->crime_type,
                    'description'     => $a->description,
                    'latitude'        => $a->latitude,
                    'longitude'       => $a->longitude,
                    'status'          => $a->status,
                    'created_at'      => $a->created_at,
                    'responder_count' => count($responderIds),
                    'i_responded'     => in_array((int)$userId, $responderIds),
                ];
            });

        return response()->json(['success' => true, 'alerts' => $alerts]);
    }

    // GET /api/sos/active-recent
    public function activeRecentAlerts(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $cutoff = now()->subMinutes(30);

        $alerts = SosAlert::with(['user', 'responders'])
            ->where('status', 'active')
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($a) use ($userId) {
                $responderIds = $a->responders ? $a->responders->pluck('responder_id')->toArray() : [];
                return [
                    'id'              => $a->id,
                    'victim_name'     => $a->user ? $a->user->name : 'Anonymous',
                    'location_text'   => $a->location_text,
                    'crime_type'      => $a->crime_type,
                    'description'     => $a->description,
                    'latitude'        => $a->latitude,
                    'longitude'       => $a->longitude,
                    'status'          => $a->status,
                    'created_at'      => $a->created_at,
                    'responder_count' => count($responderIds),
                    'i_responded'     => in_array((int)$userId, $responderIds),
                    'minutes_ago'     => $a->created_at
                        ? (int) now()->diffInMinutes(Carbon::parse($a->created_at))
                        : null,
                ];
            });

        return response()->json(['success' => true, 'alerts' => $alerts, 'count' => $alerts->count()]);
    }

    // POST /api/sos/cancel
    public function cancelAlert(Request $request)
    {
        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        $sosId = $request->sos_id;
        if (!$sosId) {
            return response()->json(['success' => false, 'message' => 'sos_id required'], 422);
        }

        /** @var SosAlert $sos */
        $sos = SosAlert::find($sosId);
        if (!$sos) {
            return response()->json(['success' => false, 'message' => 'SOS not found'], 404);
        }

        if ($userId && $sos->user_id && $sos->user_id != $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $sos->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'SOS cancelled.']);
    }
}