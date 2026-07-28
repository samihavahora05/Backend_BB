<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
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

        $consultation = Consultation::create($validated);

        // Dispatch queued contact form confirmation email
        SendQueuedEmailJob::dispatch(
            $consultation->email,
            new ContactFormMail($consultation->name, 'Consultation Request Booking', $consultation->query ?? 'No query specified.'),
            'Consultation Request Received'
        );

        SendAdminNotificationJob::dispatch(
            'New Consultation Booking',
            "A new consultation has been booked by {$consultation->name} ({$consultation->email}).",
            $consultation->toArray()
        );

        return response()->json([
            'message' => 'Consultation booked successfully. We will contact you soon.',
            'data' => $consultation
        ], 201);
    }

    /**
     * Admin method to view all consultations
     */
    public function index(Request $request)
    {
        $query = Consultation::query();

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['name', 'created_at'],
            ['name', 'email', 'query']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Admin method to view a specific consultation
     */
    public function show($id)
    {
        $consultation = Consultation::findOrFail($id);
        return response()->json($consultation);
    }

    /**
     * Admin method to update status of a consultation
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,contacted,resolved',
        ]);

        $consultation = Consultation::findOrFail($id);
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
        $consultation = Consultation::findOrFail($id);
        $consultation->delete();

        return response()->json(['message' => 'Consultation deleted successfully']);
    }
}
