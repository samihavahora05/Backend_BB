<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Mail\ContactFormMail;
use App\Jobs\SendQueuedEmailJob;
use App\Jobs\SendAdminNotificationJob;

class ConsultationController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to book a consultation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'query' => 'nullable|string',
            'preferred_date' => 'nullable|date',
        ]);

        $leadData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'message' => $validated['query'] ?? null,
            'type' => 'Book Consultation',
            'source' => 'website',
            'status' => 'new'
        ];

        if (!empty($validated['preferred_date'])) {
            $leadData['internal_notes'] = 'Preferred Date: ' . $validated['preferred_date'];
        }

        $consultation = Lead::create($leadData);

        try {
            // Dispatch queued contact form confirmation email
            SendQueuedEmailJob::dispatch(
                $consultation->email,
                new ContactFormMail($consultation->name, 'Consultation Request Booking', $consultation->message ?? 'No query specified.'),
                'Consultation Request Received'
            );

            SendAdminNotificationJob::dispatch(
                'New Consultation Booking',
                "A new consultation has been booked by {$consultation->name} ({$consultation->email}).",
                $consultation->toArray()
            );
        } catch (\Exception $e) {
            // Silently catch email failures so the user still gets a success message
            // Useful if SMTP or queue connection is misconfigured on production
            \Illuminate\Support\Facades\Log::error('Consultation Booking Email Failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Consultation booked successfully. We will contact you soon.',
            'data' => $consultation
        ], 201);
    }

    /**
     * Admin method to view all consultations (Redirected to Lead Logic conceptually, kept for legacy if needed)
     */
    public function index(Request $request)
    {
        $query = Lead::where('type', 'Book Consultation');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['name', 'created_at'],
            ['name', 'email', 'message']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Admin method to view a specific consultation
     */
    public function show($id)
    {
        $consultation = Lead::findOrFail($id);
        return response()->json($consultation);
    }

    /**
     * Admin method to update status of a consultation
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,in_progress,converted,dead,closed,spam',
        ]);

        $consultation = Lead::findOrFail($id);
        $consultation->update($validated);

        return response()->json([
            'message' => 'Consultation status updated',
            'data' => $consultation
        ]);
    }

    /**
     * Admin method to delete a consultation
     */
    public function destroy($id)
    {
        $consultation = Lead::findOrFail($id);
        $consultation->delete();

        return response()->json(['message' => 'Consultation deleted successfully']);
    }
}
