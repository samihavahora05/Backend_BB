<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Broadcast;
use Illuminate\Http\Request;

class AdminCommunicationController extends Controller
{
    public function inbox(Request $request)
    {
        $userId = $request->user()->id;
        $tab = strtolower($request->input('tab', 'inbox'));
        
        $query = MessageThread::with(['creator', 'messages' => function($q) {
            $q->latest()->limit(1);
        }, 'recipients.user']);

        if ($tab === 'sent') {
            $query->where('creator_id', $userId);
        } elseif ($tab === 'unread') {
            $query->whereHas('recipients', function($q) use ($userId) {
                $q->where('recipient_id', $userId)->whereNull('read_at');
            });
        } elseif ($tab === 'private messages' || $tab === 'private') {
            $query->where('type', 'private')
                  ->where(function($q) use ($userId) {
                      $q->where('creator_id', $userId)
                        ->orWhereHas('recipients', function($subq) use ($userId) {
                            $subq->where('recipient_id', $userId);
                        });
                  });
        } else {
            // Default: Inbox (I am a recipient)
            $query->whereHas('recipients', function($q) use ($userId) {
                $q->where('recipient_id', $userId);
            });
        }
        
        $threads = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($threads);
    }

    public function showThread($id, Request $request)
    {
        $thread = MessageThread::with(['messages.sender', 'recipients.user'])->findOrFail($id);
        
        // Mark as read
        MessageRecipient::where('message_thread_id', $id)
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'data' => $thread]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'body' => 'required|string',
            'recipient_ids' => 'required|array',
            'subject' => 'nullable|string',
        ]);

        $threadId = $request->thread_id;

        if (!$threadId) {
            $thread = MessageThread::create([
                'subject' => $request->subject ?? 'No Subject',
                'creator_id' => $request->user()->id,
                'type' => 'private',
            ]);
            $threadId = $thread->id;
        }

        $message = Message::create([
            'message_thread_id' => $threadId,
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $recipientIds = $request->recipient_ids;
        if (!in_array($request->user()->id, $recipientIds)) {
            $recipientIds[] = $request->user()->id;
        }

        foreach ($recipientIds as $recId) {
            MessageRecipient::firstOrCreate([
                'message_thread_id' => $threadId,
                'message_id' => $message->id,
                'recipient_id' => $recId,
            ], [
                'read_at' => $recId == $request->user()->id ? now() : null,
            ]);
        }

        return response()->json(['success' => true, 'data' => $message]);
    }

    public function broadcasts(Request $request)
    {
        $broadcasts = Broadcast::with('creator')->latest()->paginate($request->get('per_page', 15));
        return response()->json($broadcasts);
    }

    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'target_roles' => 'required|array',
        ]);

        $broadcast = Broadcast::create([
            'title' => $request->title,
            'content' => $request->content,
            'target_roles' => $request->target_roles,
            'created_by' => $request->user()->id,
            'sent_at' => now(), // could be scheduled later
        ]);

        // In a real app, dispatch a Job here to send emails/notifications

        return response()->json(['success' => true, 'data' => $broadcast]);
    }

    public function getAnnouncements(Request $request)
    {
        $announcements = MessageThread::where('type', 'announcement')
            ->with(['messages', 'creator'])
            ->latest()
            ->paginate($request->get('per_page', 15));
        return response()->json($announcements);
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $thread = MessageThread::create([
            'subject' => $request->subject,
            'creator_id' => $request->user()->id,
            'type' => 'announcement',
        ]);

        $message = Message::create([
            'message_thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return response()->json(['success' => true, 'data' => clone $thread->load('messages')]);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $thread = MessageThread::where('type', 'announcement')->findOrFail($id);
        $thread->update(['subject' => $request->subject]);

        $message = $thread->messages()->first();
        if ($message) {
            $message->update(['body' => $request->body]);
        }

        return response()->json(['success' => true, 'data' => clone $thread->load('messages')]);
    }

    public function deleteAnnouncement($id)
    {
        $thread = MessageThread::where('type', 'announcement')->findOrFail($id);
        $thread->delete();
        return response()->json(['success' => true, 'message' => 'Announcement deleted']);
    }

    public function deleteThread($id, Request $request)
    {
        $thread = MessageThread::findOrFail($id);
        // Soft delete the thread
        $thread->delete();
        return response()->json(['success' => true, 'message' => 'Thread deleted']);
    }

    public function deleteBroadcast($id)
    {
        $broadcast = Broadcast::findOrFail($id);
        $broadcast->delete();
        return response()->json(['success' => true, 'message' => 'Broadcast deleted']);
    }
}
