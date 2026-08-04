<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketNote;
use App\Models\User;

class AdminSupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['company.user', 'user:id,first_name,last_name,email', 'assignedAdmin:id,first_name,last_name,email']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== '') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('company', function($q2) use ($search) {
                      $q2->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($tickets);
    }

    public function show(Request $request, $id)
    {
        $ticket = SupportTicket::with([
            'company.user',
            'assignedAdmin:id,first_name,last_name,email',
            'messages.user:id,first_name,last_name,email',
            'notes.admin:id,first_name,last_name,email'
        ])->findOrFail($id);

        return response()->json(['ticket' => $ticket]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Open,In Progress,Contacted,Resolved,Closed'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = $request->status;
        $ticket->save();

        if ($ticket->user) {
            $ticket->user->notify(new \App\Notifications\PlatformNotification(
                'Ticket Status Updated',
                'Your support ticket #' . $ticket->ticket_number . ' is now marked as ' . $request->status,
                'support_ticket_status',
                ['ticket_id' => $ticket->id]
            ));
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'ticket' => $ticket
        ]);
    }

    public function assignAdmin(Request $request, $id)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        
        // Ensure the assigned user is actually an admin
        $admin = User::findOrFail($request->admin_id);
        if (!$admin->hasRole('admin') && !$admin->hasRole('super_admin')) {
            return response()->json(['message' => 'User is not an admin'], 400);
        }

        $ticket->assigned_admin_id = $request->admin_id;
        $ticket->save();

        return response()->json([
            'message' => 'Admin assigned successfully',
            'ticket' => $ticket->load('assignedAdmin:id,first_name,last_name,email')
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $user = $request->user();

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin_reply' => true,
            'attachments' => $request->attachments
        ]);

        if ($ticket->status === 'Open') {
            $ticket->status = 'In Progress';
            $ticket->save();
        }

        // Notify the ticket creator (e.g. the student or company user)
        if ($ticket->user) {
            $ticket->user->notify(new \App\Notifications\PlatformNotification(
                'Support Ticket Reply',
                'An admin has replied to your support ticket #' . $ticket->ticket_number,
                'support_ticket_reply',
                ['ticket_id' => $ticket->id]
            ));
        }

        return response()->json([
            'message' => 'Reply sent successfully',
            'reply' => $message->load('user:id,first_name,last_name,email')
        ]);
    }

    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $user = $request->user();

        $note = SupportTicketNote::create([
            'ticket_id' => $ticket->id,
            'admin_id' => $user->id,
            'note' => $request->note
        ]);

        return response()->json([
            'message' => 'Internal note added successfully',
            'note' => $note->load('admin:id,first_name,last_name,email')
        ]);
    }
}
