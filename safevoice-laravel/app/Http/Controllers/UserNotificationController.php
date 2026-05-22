<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;

/**
 * User Notification Controller
 *
 * Routes:
 *   GET  /api/notifications          → সব notifications (পাগিনেশন সহ)
 *   GET  /api/notifications/unread-count → শুধু count
 *   POST /api/notifications/mark-read   → একটা বা সব mark as read
 *   DELETE /api/notifications/{id}      → একটা delete
 */
class UserNotificationController extends Controller
{
    // ─── Helper: session থেকে user_id নাও ───────────────────────
    private function userId(Request $request): ?int
    {
        return $request->session()->get('user_id')
            ?? $request->query('user_id')
            ?? $request->input('user_id')
            ?: null;
    }

    // ─── GET /api/notifications ──────────────────────────────────
    // সব notifications, newest first, 20 per page
    public function index(Request $request)
    {
        $userId = $this->userId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notifications = UserNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
            'unread_count'  => $notifications->where('is_read', false)->count(),
        ]);
    }

    // ─── GET /api/notifications/unread-count ─────────────────────
    public function unreadCount(Request $request)
    {
        $userId = $this->userId($request);
        if (!$userId) {
            return response()->json(['count' => 0]);
        }

        $count = UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    // ─── POST /api/notifications/mark-read ───────────────────────
    // Body: { id: 5 }  → একটা mark read
    // Body: { all: true } → সব mark read
    public function markRead(Request $request)
    {
        $userId = $this->userId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($request->boolean('all')) {
            // সব unread mark as read
            UserNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
        }

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Notification ID required'], 422);
        }

        UserNotification::where('id', $id)
            ->where('user_id', $userId) // security: নিজেরটাই mark করতে পারবে
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Marked as read']);
    }

    // ─── DELETE /api/notifications/{id} ─────────────────────────
    public function destroy(Request $request, int $id)
    {
        $userId = $this->userId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        UserNotification::where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }
}