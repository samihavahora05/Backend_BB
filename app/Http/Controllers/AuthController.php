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

        $roleName = $request->role;
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web'
        ]);
        $user->assignRole($role);

        // Auto-provision profile based on role
        match ($roleName) {
            'student' => $user->studentProfile()->create(),
            'expert' => $user->expertProfile()->create(),
            'company' => $user->companyProfile()->create(),
            'college' => $user->collegeProfile()->create(),
            'intern' => $user->internProfile()->create(),
            'job-seeker' => $user->jobSeekerProfile()->create(),
            default => null,
        };

        // Dispatch Welcome Email Job
        \App\Jobs\SendWelcomeEmailJob::dispatch($user);

        if ($roleName === 'student') {
            $user->update(['status' => 'active']);
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user->load('roles'),
                'token' => $token,
                'status' => 'active'
            ], 201);
        } else {
            $user->update(['status' => 'pending_approval']);
            
            // Notify all admins about the new pending user
            $admins = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })->get();
            
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewUserPendingApproval($user));
            }
            
            return response()->json([
                'message' => 'Account created successfully. Your account is pending admin approval.',
                'user' => $user->load('roles'),
                'status' => 'pending_approval'
            ], 201);
        }
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

        // Allow multiple sessions (do not delete all tokens on login)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        
        Log::info("Successful login for user ID: {$user->id} from IP: {$request->ip()}");

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        // Auto-fix admin role if it's the admin user
        if ($user->email === 'admin@blueboxx.in' || $user->id === 1) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
            // Remove student role if they have it
            $studentRole = \Spatie\Permission\Models\Role::where('name', 'student')->where('guard_name', 'web')->first();
            if ($studentRole && $user->hasRole($studentRole)) {
                $user->removeRole($studentRole);
            }
        }
        
        $user->load('roles');
        
        // Load the specific profile based on the user's role
        if ($user->hasRole('student')) $user->load('studentProfile');
        elseif ($user->hasRole('expert')) $user->load('expertProfile');
        elseif ($user->hasRole('company')) $user->load('companyProfile');
        elseif ($user->hasRole('college')) $user->load('collegeProfile');
        elseif ($user->hasRole('intern')) $user->load('internProfile');
        elseif ($user->hasRole('job-seeker')) $user->load('jobSeekerProfile');

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
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
        Cache::put('password_reset_' . $user->email, $otp, now()->addMinutes(15));
        
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
        Cache::put('login_otp_' . $user->phone, $otp, now()->addMinutes(5));
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

        $user = User::where('phone', $request->phone)->first();

        // Allow multiple sessions (do not delete all tokens on login)
        // $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("Successful OTP login for user ID: {$user->id} from IP: {$request->ip()}");

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    // Duplicate method removed to fix fatal error
}
