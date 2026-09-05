<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\OtpEmail;
use App\Mail\PasswordResetMail;
use App\Jobs\SendQueuedEmailJob;
use Illuminate\Validation\Rules\Password;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // Validation is automatically handled by RegisterRequest
        
        $nameParts = explode(' ', trim($request->name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $requestedRole = strtolower($request->input('role', 'student') ?? 'student');
        if ($requestedRole === 'jobseeker') {
            $requestedRole = 'job-seeker';
        }

        $allowedRoles = ['student', 'intern', 'job-seeker', 'company', 'college', 'expert'];
        $roleName = in_array($requestedRole, $allowedRoles, true) ? $requestedRole : 'student';

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web'
        ]);
        $user->assignRole($role);

        // Auto-provision profile based on role
        match ($roleName) {
            'student' => $user->studentProfile()->create(),
            'expert' => $user->expertProfile()->create(['is_verified' => true, 'is_available' => true]),
            'company' => $user->companyProfile()->create(),
            'college' => $user->collegeProfile()->create(),
            'intern' => $user->internProfile()->create(),
            'job-seeker' => $user->jobSeekerProfile()->create(),
            default => $user->studentProfile()->create(),
        };

        // Student registration does not require email/SMTP.
        // Welcome emails are intentionally not dispatched here.

        // Bypass approvals and activate instantly
        $user->update(['status' => 'active']);
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user->load('roles'),
            'token' => $token,
            'status' => 'active'
        ], 201);
    }



    public function login(LoginRequest $request)
    {
        // Validation is handled automatically by LoginRequest

        $throttleKey = 'login:' . strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Account locked due to too many failed login attempts for email: {$request->email} from IP: {$request->ip()}");
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300); // 5 minutes lockout
            Log::warning("Failed login attempt for email: {$request->email} from IP: {$request->ip()}");
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        
        RateLimiter::clear($throttleKey);

        // Admin and Super Admin users bypass status restrictions
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        if (!$isAdmin) {
            if ($user->status === 'pending_approval') {
                return response()->json(['message' => 'Your account is pending admin approval.'], 403);
            }
            if ($user->status === 'suspended') {
                return response()->json(['message' => 'Your account has been suspended.'], 403);
            }
            if ($user->status === 'rejected') {
                return response()->json(['message' => 'Your account registration was rejected.'], 403);
            }
        }

        // Allow multiple logins / sessions across different devices (do not delete all existing tokens on login)
        $deviceInfo = $this->getDeviceInfo($request);
        $token = $user->createToken($deviceInfo['token_name'])->plainTextToken;

        // Record admin session if user is admin / super_admin
        if ($isAdmin) {
            try {
                \App\Models\AdminSession::create([
                    'user_id' => $user->id,
                    'login_at' => now(),
                    'ip_address' => $request->ip(),
                    'device' => $deviceInfo['device'],
                    'browser' => $deviceInfo['browser'],
                    'location' => 'Unknown',
                    'status' => 'active',
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record AdminSession: ' . $e->getMessage());
            }
        }
        
        Log::info("Successful login for user ID: {$user->id} on {$deviceInfo['device']} ({$deviceInfo['browser']}) from IP: {$request->ip()}");

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        $user->load('roles');
        
        // Load the specific profile based on the user's role
        if ($user->hasRole('student')) $user->load('studentProfile');
        elseif ($user->hasRole('expert')) $user->load('expertProfile');
        elseif ($user->hasRole('company')) $user->load('companyProfile');
        elseif ($user->hasRole('college')) $user->load('collegeProfile');
        elseif ($user->hasRole('intern')) $user->load('internProfile');
        elseif ($user->hasRole('job-seeker') || $user->hasRole('jobseeker')) $user->load('jobSeekerProfile');

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Delete only current device's access token so other active devices remain logged in
            $user->currentAccessToken()?->delete();

            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                try {
                    \App\Models\AdminSession::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('ip_address', $request->ip())
                        ->latest('login_at')
                        ->first()
                        ?->update(['status' => 'logged_out', 'logout_at' => now()]);
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        
        Log::info("Password changed for user ID: {$user->id}");

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();
        
        $otp = rand(100000, 999999);
        // Using password_reset_ prefix
        Cache::put('password_reset_' . $user->email, $otp, now()->addMinutes(30));
        
        SendQueuedEmailJob::dispatch(
            $user->email,
            new PasswordResetMail($otp),
            'Password Reset Verification Code'
        );
        if ($user->phone) {
            \App\Jobs\SendSmsOtpJob::dispatch($user->phone, $otp);
        }


        return response()->json([
            'message' => 'Password reset OTP sent to your email and phone.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
        ]);

        $cachedOtp = Cache::get('password_reset_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        Cache::forget('password_reset_' . $request->email);

        // Optionally revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully. Please log in with your new password.']);
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,linkedin,github',
            'token' => 'required|string',
        ]);

        // Future-ready scaffold for social login via Laravel Socialite
        return response()->json([
            'message' => 'Social login endpoint is future-ready. Please integrate Socialite when needed.',
            'provider' => $request->provider
        ], 501);
    }

    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone'
        ]);

        $user = User::where('phone', $request->phone)->first();

        $cooldownKey = 'login_otp_cooldown:' . $user->phone;
        if (Cache::has($cooldownKey)) {
            return response()->json(['message' => 'Please wait 30 seconds before requesting a new OTP.'], 429);
        }

        $otp = rand(100000, 999999);
        Cache::put('login_otp_' . $user->phone, $otp, now()->addMinutes(30));
        Cache::put($cooldownKey, true, now()->addSeconds(30));

        \App\Jobs\SendSmsOtpJob::dispatch($user->phone, $otp);

        return response()->json([
            'message' => 'Login OTP sent to your phone.'
        ]);
    }

    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'otp' => 'required|numeric'
        ]);

        $throttleKey = 'login_otp:' . $request->phone . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Account locked due to too many failed OTP login attempts for phone: {$request->phone}");
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        $cachedOtp = Cache::get('login_otp_' . $request->phone);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            RateLimiter::hit($throttleKey, 300); // 5 minutes lockout
            Log::warning("Failed OTP login attempt for phone: {$request->phone}");
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        RateLimiter::clear($throttleKey);
        Cache::forget('login_otp_' . $request->phone);

        // Allow multiple logins / sessions across different devices
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        $deviceInfo = $this->getDeviceInfo($request);
        $token = $user->createToken($deviceInfo['token_name'])->plainTextToken;

        if ($isAdmin) {
            try {
                \App\Models\AdminSession::create([
                    'user_id' => $user->id,
                    'login_at' => now(),
                    'ip_address' => $request->ip(),
                    'device' => $deviceInfo['device'],
                    'browser' => $deviceInfo['browser'],
                    'location' => 'Unknown',
                    'status' => 'active',
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record AdminSession for OTP login: ' . $e->getMessage());
            }
        }

        Log::info("Successful OTP login for user ID: {$user->id} on {$deviceInfo['device']} ({$deviceInfo['browser']}) from IP: {$request->ip()}");

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    /**
     * Parse User-Agent and Request to determine device type, browser, and token name.
     */
    private function getDeviceInfo(Request $request): array
    {
        $ua = strtolower((string)$request->userAgent());
        $device = 'Desktop';
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            $device = 'Mobile';
        } elseif (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) {
            $device = 'Tablet';
        }

        $browser = 'Browser';
        if (strpos($ua, 'firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($ua, 'edge') !== false || strpos($ua, 'edg/') !== false) {
            $browser = 'Edge';
        } elseif (strpos($ua, 'chrome') !== false || strpos($ua, 'crios') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($ua, 'safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($ua, 'opera') !== false || strpos($ua, 'opr/') !== false) {
            $browser = 'Opera';
        }

        $tokenName = $request->input('device_name') ?: ($device . ' - ' . $browser . ' (' . ($request->ip() ?? 'Unknown IP') . ')');

        return [
            'device' => $device,
            'browser' => $browser,
            'token_name' => $tokenName,
        ];
    }
}
