<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * List user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('unread') && $request->unread === 'true') {
            $query->unread();
        }

        // Limit for dropdown (recent only)
        $limit = $request->get('limit', 50);

        $notifications = $query->take($limit)->get();
        $unreadCount = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $userId = Auth::id();
        $success = $this->notificationService->markAsRead($id, $userId);

        if (!$success) {
            return response()->json(['message' => 'Notificação não encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Notificação marcada como lida.',
            'unread_count' => $this->notificationService->getUnreadCount($userId),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = Auth::id();
        $count = $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'message' => "{$count} notificações marcadas como lidas.",
            'unread_count' => 0,
        ]);
    }
}
