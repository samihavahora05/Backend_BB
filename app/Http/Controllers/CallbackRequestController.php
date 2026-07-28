<?php

namespace App\Http\Controllers;

use App\Models\CallbackRequest;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Mail\ContactFormMail;
use App\Jobs\SendQueuedEmailJob;

class CallbackRequestController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to request a callback
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'query' => 'nullable|string',
        ]);

        $callback = CallbackRequest::create($validated);

        // Dispatch queued callback request confirmation email
        SendQueuedEmailJob::dispatch(
            $callback->email,
            new ContactFormMail($callback->name, 'Callback Request Booking', $callback->query ?? 'No query specified.'),
            'Callback Request Received'
        );

        return response()->json([
            'message' => 'Callback requested successfully. We will call you back soon.',
            'data' => $callback
        ], 201);
    }

    /**
     * Admin method to view all callback requests
     */
    public function index(Request $request)
    {
        $query = CallbackRequest::query();

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['name', 'created_at'],
            ['name', 'email', 'query']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Admin method to view a specific callback request
     */
    public function show($id)
    {
        $callback = CallbackRequest::findOrFail($id);
        return response()->json($callback);
    }

    /**
     * Admin method to update status/assignee of a callback request
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,contacted,resolved',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $callback = CallbackRequest::findOrFail($id);
        $callback->update($validated);

        return response()->json([
            'message' => 'Callback request updated',
            'data' => $callback
        ]);
    }

    /**
     * Admin method to delete a callback request
     */
    public function destroy($id)
    {
        $callback = CallbackRequest::findOrFail($id);
        $callback->delete();

        return response()->json(['message' => 'Callback request deleted successfully']);
    }
}
