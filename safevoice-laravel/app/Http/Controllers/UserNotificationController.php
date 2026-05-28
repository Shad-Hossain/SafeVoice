<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;

class UserNotificationController extends Controller
{
    /**
     * ✅ user_id শুধু token/session থেকে নাও
     * NEVER query param বা input থেকে নেওয়া হবে না
     */
    private function getAuthUserId(Request $request): ?int
    {
        if ($user = $request->user()) {
            return $user->id;
        }
        return $request->session()->get('user_id') ?: null;
    }

    // GET /api/notifications
    public function index(Request $request)
    {
        $userId = $this->getAuthUserId($request);
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

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        $userId = $this->getAuthUserId($request);
        if (!$userId) {
            return response()->json(['count' => 0]);
        }

        $count = UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    // POST /api/notifications/mark-read
    public function markRead(Request $request)
    {
        $userId = $this->getAuthUserId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($request->boolean('all')) {
            UserNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);
            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
        }

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Notification ID required'], 422);
        }

        // ✅ user_id check করে — নিজেরটাই mark করতে পারবে
        UserNotification::where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Marked as read']);
    }

    // DELETE /api/notifications/{id}
    public function destroy(Request $request, int $id)
    {
        $userId = $this->getAuthUserId($request);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ নিজেরটাই delete করতে পারবে
        UserNotification::where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }
}
