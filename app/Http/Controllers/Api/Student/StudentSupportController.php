<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\CompanyProfile;

class StudentSupportController extends Controller
{
    /**
     * Get all support tickets for the logged-in student.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tickets = SupportTicket::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status,
                'priority' => $t->priority,
                'created_at' => $t->created_at->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    /**
     * Create a new support ticket from a student.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'priority' => 'nullable|in:Low,Normal,High,Urgent',
        ]);

        $user = $request->user();
        $ticketNumber = 'TKT-' . strtoupper(uniqid());

        // Get or create a dummy "Student Support" company profile.
        // This gracefully satisfies the DB's NOT NULL constraint on company_id,
        // and allows the admin panel to display these tickets properly.
        $dummyCompany = CompanyProfile::firstOrCreate(
            ['company_name' => 'Student Support'],
            ['user_id'      => $user->id]
        );

        $ticket = SupportTicket::create([
            'ticket_number' => $ticketNumber,
            'user_id'       => $user->id,
            'company_id'    => $dummyCompany->id,
            'subject'       => $request->subject,
            'description'   => $request->message,
            'priority'      => $request->priority ?? 'Normal',
            'status'        => 'Open',
        ]);

        if ($ticket) {
            SupportTicketMessage::create([
                'ticket_id'      => $ticket->id,
                'user_id'        => $user->id,
                'message'        => $request->message,
                'is_admin_reply' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data'    => ['ticket_number' => $ticketNumber]
        ], 201);
    }

    /**
     * Get details of a specific support ticket including its messages.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $ticket = SupportTicket::with(['messages.user:id,first_name,last_name,email'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /**
     * Reply to a specific support ticket.
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $ticket = SupportTicket::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $message = SupportTicketMessage::create([
            'ticket_id'      => $ticket->id,
            'user_id'        => $user->id,
            'message'        => $request->message,
            'is_admin_reply' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully',
            'data'    => $message->load('user:id,first_name,last_name,email')
        ]);
    }
}
