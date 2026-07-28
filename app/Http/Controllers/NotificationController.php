<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Traits\PaginateQuery;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use PaginateQuery;

    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->notifications();

        // Using our PaginateQuery trait
        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['created_at'],
            []
        );

        return response()->json([
            'success' => true,
            'data' => $paginated['data'],
            'pagination' => $paginated['pagination']
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        
        if ($request->has('id')) {
            $notification = $user->notifications()->where('id', $request->input('id'))->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read successfully'
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Store device token for FCM.
     */
    public function storeDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:web,android,ios'
        ]);

        DeviceToken::updateOrCreate(
            [
                'token' => $request->token,
            ],
            [
                'user_id' => $request->user()->id,
                'device_type' => $request->input('device_type', 'web')
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully'
        ]);
    }
}
