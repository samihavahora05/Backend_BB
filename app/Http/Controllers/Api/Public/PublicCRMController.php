<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class PublicCRMController extends Controller
{
    /**
     * Submit a contact form / lead generation
     * POST /api/public/contact
     */
    public function submitLead(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'nullable|string|max:20',
            'subject'           => 'nullable|string|max:255',
            'message'           => 'nullable|string|max:5000',
            'course_interested' => 'nullable|string|max:255',
            'source'            => 'nullable|string|max:100', // e.g., 'Website Contact Form'
            'source_page'       => 'nullable|string|max:255', // e.g., URL where they submitted
        ]);

        $type = $data['subject'] ?? 'Contact Inquiry';

        $lead = Lead::create([
            'type'              => $type,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'subject'           => $data['subject'] ?? null,
            'message'           => $data['message'] ?? null,
            'course_interested' => $data['course_interested'] ?? null,
            'source'            => $data['source'] ?? 'Website',
            'source_page'       => $data['source_page'] ?? null,
            'status'            => 'new',
            'ip_address'        => $request->ip(),
            'browser'           => substr($request->userAgent() ?? '', 0, 255),
        ]);

        // Dispatch background queued emails
        \Illuminate\Support\Facades\Notification::route('mail', 'info.blueboxx@gmail.com')
            ->notify(new \App\Notifications\ContactInquiryAdminNotification($lead));
        
        \Illuminate\Support\Facades\Notification::route('mail', $lead->email)
            ->notify(new \App\Notifications\ContactInquiryUserNotification($lead));

        return response()->json([
            'success' => true,
            'message' => 'Thank you! We have received your inquiry and will get back to you shortly.',
        ]);
    }

    /**
     * Submit a support ticket (Auth required)
     * POST /api/public/support/tickets
     */
    public function createTicket(Request $request)
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'message'  => 'required|string|max:2000',
        ]);

        $ticket = SupportTicket::create([
            'user_id'  => $request->user()->id,
            'subject'  => $data['subject'],
            'priority' => $data['priority'],
            'status'   => 'Open',
        ]);

        // Create the initial message in the thread
        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully!',
            'data'    => ['ticket_id' => $ticket->id]
        ], 201);
    }
}
