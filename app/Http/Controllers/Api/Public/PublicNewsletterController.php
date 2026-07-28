<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class PublicNewsletterController extends Controller
{
    /**
     * Subscribe to the newsletter
     * POST /api/public/newsletter/subscribe
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $subscriber = NewsletterSubscriber::where('email', $request->email)->first();

        if ($subscriber) {
            if (!$subscriber->is_active) {
                $subscriber->update(['is_active' => true, 'ip_address' => $request->ip()]);
                // Optionally dispatch welcome back email
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already subscribed to our newsletter!',
                ], 400);
            }
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email'      => $request->email,
                'is_active'  => true,
                'ip_address' => $request->ip(),
            ]);

            // Also create a Lead entry so it appears in the CRM Dashboard
            \App\Models\Lead::create([
                'type' => 'Newsletter Subscriber',
                'name' => 'Subscriber', // Name isn't provided in newsletter form
                'email' => $request->email,
                'status' => 'new',
                'source' => 'Website Footer',
                'ip_address' => $request->ip(),
                'browser' => substr($request->userAgent() ?? '', 0, 255),
            ]);

            // Dispatch background queued welcome email
            \Illuminate\Support\Facades\Notification::route('mail', $subscriber->email)
                ->notify(new \App\Notifications\NewsletterWelcomeNotification($subscriber));
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!',
        ]);
    }

    /**
     * Unsubscribe from the newsletter
     * POST /api/public/newsletter/unsubscribe
     */
    public function unsubscribe(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $subscriber = NewsletterSubscriber::where('email', $request->email)->first();
        
        if ($subscriber && $subscriber->is_active) {
            $subscriber->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => 'You have successfully unsubscribed.']);
        }
        
        return response()->json(['success' => false, 'message' => 'Email not found or already unsubscribed.'], 400);
    }
}
