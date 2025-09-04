<?php
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\V1\NotificationResource;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /**
     * Display a listing of the notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::where('user_id', $user->id)
            ->where('deleted_flag', 'N')
            ->latest()
            ->paginate(20);

        // Mark all notifications as read
        Notification::where('user_id', $user->id)
            ->where('receive_flag', 'N')
            ->update(['receive_flag' => 'Y']);

        return $this->sendResponse('Notifications retrieved successfully', [
            'notifications' => NotificationResource::collection($notifications),
            'pagination' => [
                'total' => $notifications->total(),
                'count' => $notifications->count(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Get the count of unread notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = Notification::where('user_id', $user->id)
            ->where('receive_flag', 'N')
            ->where('deleted_flag', 'N')
            ->count();

        return $this->sendResponse('Unread notifications count retrieved successfully', [
            'count' => $count,
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        Notification::where('user_id', $user->id)
            ->where('receive_flag', 'N')
            ->update(['receive_flag' => 'Y']);

        return $this->sendResponse('All notifications marked as read');
    }

    /**
     * Get user notifications (new system)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = UserNotification::forUser($user->id)
            ->byPriority()
            ->paginate(20);

        return $this->sendResponse('User notifications retrieved successfully', [
            'notifications' => $notifications->items(),
            'unread_count' => UserNotification::getUnreadCountForUser($user->id),
            'pagination' => [
                'total' => $notifications->total(),
                'count' => $notifications->count(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Get unread count for user notifications (new system)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserNotificationCount(Request $request)
    {
        $user = $request->user();
        $count = UserNotification::getUnreadCountForUser($user->id);

        return $this->sendResponse('User notification count retrieved successfully', [
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark a specific user notification as read
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markUserNotificationAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = UserNotification::forUser($user->id)->find($id);

        if (!$notification) {
            return $this->sendError('Notification not found', null, 404);
        }

        $notification->markAsRead();

        return $this->sendResponse('Notification marked as read', [
            'notification' => $notification->fresh(),
            'unread_count' => UserNotification::getUnreadCountForUser($user->id),
        ]);
    }

    /**
     * Mark all user notifications as read
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllUserNotificationsAsRead(Request $request)
    {
        $user = $request->user();
        UserNotification::forUser($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return $this->sendResponse('All user notifications marked as read', [
            'unread_count' => 0,
        ]);
    }
}
