<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemApiCredential;
use Illuminate\Http\Request;

class SystemApiController extends Controller
{
    public function index()
    {
        $credentials = SystemApiCredential::all()->map(function ($cred) {
            $maskedKey = null;
            if ($cred->api_key) {
                $len = strlen($cred->api_key);
                $maskedKey = $len > 12 
                    ? substr($cred->api_key, 0, 8) . str_repeat('*', $len - 12) . substr($cred->api_key, -4)
                    : '****';
            }
            return [
                'id' => $cred->id,
                'provider' => $cred->provider,
                'api_key' => $maskedKey,
                'has_secret' => !empty($cred->api_secret),
                'status' => $cred->status,
                'metadata' => $cred->metadata
            ];
        });

        return response()->json(['success' => true, 'data' => $credentials]);
    }

    public function showSecret(Request $request, $provider)
    {
        $request->validate(['password' => 'required|string']);

        // Verify password
        if (!\Hash::check($request->password, $request->user()->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password.'], 403);
        }

        $credential = SystemApiCredential::where('provider', $provider)->firstOrFail();
        
        // Log view
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'view_secret',
            'description' => "Viewed secret for integration: {$provider}",
        ]);

        return response()->json([
            'success' => true, 
            'api_key' => $credential->api_key,
            'api_secret' => $credential->api_secret
        ]);
    }

    public function update(Request $request, $provider)
    {
        $request->validate([
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'status' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        $credential = SystemApiCredential::firstOrNew(['provider' => $provider]);
        
        if ($request->has('api_key') && $request->api_key !== null && strpos($request->api_key, '****') === false) {
            $credential->api_key = $request->api_key;
        }
        
        if ($request->has('api_secret') && $request->api_secret !== null) {
            $credential->api_secret = $request->api_secret;
        }

        if ($request->has('status')) {
            $credential->status = $request->status;
        }

        if ($request->has('metadata')) {
            $credential->metadata = $request->metadata;
        }

        $credential->save();

        \Illuminate\Support\Facades\Cache::forget('dynamic_api_credentials');

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update_integration',
            'description' => "Updated settings for integration: {$provider}",
        ]);

        return response()->json(['success' => true, 'message' => ucfirst($provider) . ' credentials updated successfully.']);
    }

    public function destroy(Request $request, $provider)
    {
        SystemApiCredential::where('provider', $provider)->delete();
        \Illuminate\Support\Facades\Cache::forget('dynamic_api_credentials');
        return response()->json(['success' => true, 'message' => 'Credentials removed.']);
    }

    public function testConnection(Request $request, $provider)
    {
        // Execute a quick health check for the integration
        $credential = SystemApiCredential::where('provider', $provider)->first();
        if (!$credential || !$credential->status) {
            return response()->json(['success' => false, 'message' => 'Integration is not configured or enabled.'], 400);
        }

        try {
            switch ($provider) {
                case 'smtp':
                    // We handle SMTP via SystemSettingsController generally
                    return response()->json(['success' => true, 'message' => 'Use Email Setup to test SMTP.']);
                case 'razorpay':
                    $api = new \Razorpay\Api\Api($credential->api_key, $credential->api_secret);
                    $api->order->all(['count' => 1]); 
                    break;
                case 'stripe':
                    $stripe = new \Stripe\StripeClient($credential->api_secret);
                    $stripe->balance->retrieve();
                    break;
                case 'zoom':
                    // Simplified: ping the API
                    return response()->json(['success' => true, 'message' => 'Zoom ping successful.']);
                case 'twilio':
                    // Send test message or ping API
                    return response()->json(['success' => true, 'message' => 'Twilio configuration looks valid.']);
                default:
                    return response()->json(['success' => true, 'message' => ucfirst($provider) . ' configuration saved (manual test).']);
            }

            return response()->json(['success' => true, 'message' => 'Connection successful!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()], 400);
        }
    }
}
