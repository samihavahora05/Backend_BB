<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminSessionController extends Controller
{
    public function index(Request $request)
    {
        // Fallback to avoid errors if session table doesn't have records or isn't used
        try {
            $sessions = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->select(
                    'sessions.id',
                    'sessions.ip_address',
                    'sessions.user_agent',
                    'sessions.last_activity',
                    'users.id as user_id',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('sessions.last_activity', 'desc')
                ->get();
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }

        $formatted = $sessions->map(function($session) use ($request) {
            $isCurrent = false; // with Sanctum, session ID might not match directly if using tokens. We mark as false for now.
            if ($request->hasSession() && $session->id === $request->session()->getId()) {
                $isCurrent = true;
            }
            
            $ua = strtolower((string)$session->user_agent);
            $device = 'Desktop';
            if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
                $device = 'Mobile';
            } elseif (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) {
                $device = 'Tablet';
            }

            $browser = 'Unknown';
            if (strpos($ua, 'firefox') !== false) {
                $browser = 'Firefox';
            } elseif (strpos($ua, 'chrome') !== false) {
                $browser = 'Chrome';
            } elseif (strpos($ua, 'safari') !== false) {
                $browser = 'Safari';
            } elseif (strpos($ua, 'edge') !== false) {
                $browser = 'Edge';
            }

            return [
                'id' => $session->id,
                'user' => trim($session->first_name . ' ' . $session->last_name) ?: 'Unknown User',
                'email' => $session->email,
                'ip' => $session->ip_address,
                'device' => $device,
                'browser' => $browser,
                'location' => 'Mapped via IP',
                'lastActive' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'isCurrent' => $isCurrent,
                'isSuspicious' => false
            ];
        });

        return response()->json(['data' => $formatted]);
    }

    public function destroy($id)
    {
        DB::table('sessions')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function destroyOther(Request $request)
    {
        if ($request->hasSession()) {
            $currentId = $request->session()->getId();
            DB::table('sessions')->where('id', '!=', $currentId)->delete();
        } else {
            // If using sanctum token, we might just clear everything except current token's session if possible.
            // For now, clear all if no session is identified to simulate "logout all"
            DB::table('sessions')->truncate();
        }
        return response()->json(['success' => true]);
    }
}
