<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        if (!$companyId) {
            return response()->json(['message' => 'Company profile not found'], 404);
        }

        $tickets = SupportTicket::where('company_id', $companyId)
            ->with(['messages', 'assignedAdmin:id,first_name,last_name,email'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Normal,High,Urgent',
            'description' => 'required|string',
            'attachment' => 'nullable|file|max:5120'
        ]);

        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        if (!$companyId) {
            return response()->json(['message' => 'Company profile not found'], 404);
        }

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'company_id' => $companyId,
            'user_id' => $user->id,
            'subject' => $request->subject,
            'priority' => $request->priority,
            'description' => $request->description,
            'status' => 'Open'
        ]);

        $attachments = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('support_attachments', 'public');
            $attachments = [$path];
        }

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->description,
            'is_admin_reply' => false,
            'attachments' => $attachments
        ]);

        return response()->json([
            'message' => 'Ticket submitted successfully',
            'ticket' => $ticket->load('messages')
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        $ticket = SupportTicket::where('company_id', $companyId)
            ->with(['messages.user:id,first_name,last_name,email', 'assignedAdmin:id,first_name,last_name,email'])
            ->findOrFail($id);

        return response()->json(['ticket' => $ticket]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array'
        ]);

        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        $ticket = SupportTicket::where('company_id', $companyId)->findOrFail($id);

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin_reply' => false,
            'attachments' => $request->attachments
        ]);

        // If ticket was closed, maybe reopen it or keep it closed? Reopen usually.
        if ($ticket->status === 'Closed' || $ticket->status === 'Resolved') {
            $ticket->status = 'Open';
            $ticket->save();
        }

        return response()->json([
            'message' => 'Reply added successfully',
            'reply' => $message->load('user:id,first_name,last_name,email')
        ]);
    }
}
