<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MessageRecipient;

class MessageController extends Controller
{
    /**
     * Get unread message count and latest messages for the authenticated user.
     */
    public function unreadSummary(Request $request)
    {
        $user = $request->user();

        // Get count of unread messages for this user
        $unreadCount = MessageRecipient::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Get latest 5 unread messages with sender details
        $latestMessages = MessageRecipient::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->with(['message.sender:id,first_name,last_name,avatar'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($recipient) {
                return [
                    'id' => $recipient->message_id,
                    'text' => \Illuminate\Support\Str::limit($recipient->message->body ?? '', 50),
                    'sender' => $recipient->message->sender ?? null,
                    'time' => $recipient->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
                'messages' => $latestMessages
            ]
        ]);
    }
}
