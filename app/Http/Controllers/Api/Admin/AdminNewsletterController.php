<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class AdminNewsletterController extends Controller
{
    /**
     * List all subscribers
     */
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($s = $request->query('search')) {
            $query->where('email', 'like', "%{$s}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $subscribers = $query->latest()->paginate((int)$request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $subscribers->items(),
            'pagination' => [
                'current_page' => $subscribers->currentPage(),
                'last_page'    => $subscribers->lastPage(),
                'total'        => $subscribers->total(),
            ]
        ]);
    }

    /**
     * Export all active subscribers (For Mailchimp/SendGrid)
     */
    public function exportActive(Request $request)
    {
        $subscribers = NewsletterSubscriber::where('is_active', true)->pluck('email');
        
        // Return a plain text comma-separated list or JSON array for easy copy-pasting
        return response()->json([
            'success' => true,
            'data' => $subscribers
        ]);
    }

    /**
     * Delete a subscriber
     */
    public function destroy($id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);
        $subscriber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscriber deleted successfully.'
        ]);
    }
}
