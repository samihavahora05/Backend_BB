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
        $currentUser = $request->user();
        $currentIp = $request->ip();
        $currentTokenId = $currentUser?->currentAccessToken()?->id;

        // 1. Try to fetch from admin_sessions table
        try {
            $records = \App\Models\AdminSession::with('user')
                ->where('status', 'active')
                ->orderBy('login_at', 'desc')
                ->get();

            if ($records->isNotEmpty()) {
                $formatted = $records->map(function ($session) use ($currentIp, $currentUser) {
                    $isCurrent = false;
                    if ($currentUser && $session->user_id === $currentUser->id && $session->ip_address === $currentIp) {
                        $isCurrent = true;
                    }

                    return [
                        'id' => $session->id,
                        'user' => $session->user ? trim($session->user->first_name . ' ' . $session->user->last_name) : 'Admin User',
                        'email' => $session->user ? $session->user->email : 'N/A',
                        'ip' => $session->ip_address ?? 'N/A',
                        'device' => $session->device ?? 'Desktop',
                        'browser' => $session->browser ?? 'Unknown',
                        'location' => $session->location ?? 'Mapped via IP',
                        'lastActive' => $session->login_at ? $session->login_at->diffForHumans() : 'Just now',
                        'isCurrent' => $isCurrent,
                        'isSuspicious' => false
                    ];
                });

                return response()->json(['data' => $formatted]);
            }
        } catch (\Exception $e) {
            // Fall through to other sources
        }

        // 2. Fallback to personal_access_tokens for admin users
        try {
            $tokens = DB::table('personal_access_tokens')
                ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
                ->where('personal_access_tokens.tokenable_type', 'App\\Models\\User')
                ->select(
                    'personal_access_tokens.id',
                    'personal_access_tokens.name',
                    'personal_access_tokens.last_used_at',
                    'personal_access_tokens.created_at',
                    'users.id as user_id',
                    'users.first_name',
                    'users.last_name',
                    'users.email'
                )
                ->orderBy('personal_access_tokens.created_at', 'desc')
                ->get();

            if ($tokens->isNotEmpty()) {
                $formatted = $tokens->map(function ($token) use ($currentTokenId, $currentIp) {
                    $isCurrent = ($currentTokenId && $token->id == $currentTokenId);
                    $lastActiveTime = $token->last_used_at ?: $token->created_at;

                    // Parse token name if available (e.g., "Desktop - Chrome (127.0.0.1)")
                    $deviceName = $token->name ?: 'Desktop / Mobile Device';

                    return [
                        'id' => $token->id,
                        'user' => trim($token->first_name . ' ' . $token->last_name) ?: 'Admin User',
                        'email' => $token->email,
                        'ip' => $currentIp,
                        'device' => $deviceName,
                        'browser' => 'Web App / Browser',
                        'location' => 'Active Session',
                        'lastActive' => $lastActiveTime ? Carbon::parse($lastActiveTime)->diffForHumans() : 'Just now',
                        'isCurrent' => $isCurrent,
                        'isSuspicious' => false
                    ];
                });

                return response()->json(['data' => $formatted]);
            }
        } catch (\Exception $e) {
            // Fall through
        }

        // 3. Fallback to sessions table
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

            $formatted = $sessions->map(function($session) use ($request) {
                $isCurrent = false;
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
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }

    public function destroy($id)
    {
        // 1. Mark logged_out in admin_sessions
        try {
            \App\Models\AdminSession::where('id', $id)->update([
                'status' => 'logged_out',
                'logout_at' => now(),
            ]);
        } catch (\Exception $e) {}

        // 2. Delete token if id matches personal_access_tokens
        try {
            DB::table('personal_access_tokens')->where('id', $id)->delete();
        } catch (\Exception $e) {}

        // 3. Delete session if id matches sessions table
        try {
            DB::table('sessions')->where('id', $id)->delete();
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Session terminated successfully']);
    }

    public function destroyOther(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $currentTokenId = $user->currentAccessToken()?->id;

            // Revoke all other personal access tokens for this user
            if ($currentTokenId) {
                $user->tokens()->where('id', '!=', $currentTokenId)->delete();
            }

            // Mark other admin_sessions as logged out
            try {
                \App\Models\AdminSession::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->where('ip_address', '!=', $request->ip())
                    ->update([
                        'status' => 'logged_out',
                        'logout_at' => now(),
                    ]);
            } catch (\Exception $e) {}
        }

        if ($request->hasSession()) {
            $currentId = $request->session()->getId();
            DB::table('sessions')->where('id', '!=', $currentId)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Other device sessions terminated successfully']);
    }
}
