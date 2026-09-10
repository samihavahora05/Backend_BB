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
use App\Services\OtpVerificationService;
use App\Services\AuditLogService;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected OtpVerificationService $otpService;

    public function __construct(OtpVerificationService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * User registration with role assignment & email OTP dispatch.
     */
    public function register(RegisterRequest $request)
    {
        $normalizedEmail = strtolower(trim($request->email));

        $requestedRole = strtolower($request->input('role', 'student') ?? 'student');
        if ($requestedRole === 'jobseeker') {
            $requestedRole = 'job-seeker';
        }

        $allowedRoles = ['student', 'intern', 'job-seeker', 'company', 'college', 'expert'];
        $roleName = in_array($requestedRole, $allowedRoles, true) ? $requestedRole : 'student';

        // 1. Check if email already exists in database
        $existingUser = User::where('email', $normalizedEmail)->first();
        if ($existingUser) {
            $existingRole = $existingUser->roles->first()?->name ?? 'student';
            $existingRoleDisplay = ucfirst(str_replace(['_', '-'], ' ', $existingRole));
            $newRoleDisplay = ucfirst(str_replace(['_', '-'], ' ', $roleName));

            // Case A: Different role requested with same email -> STRICTLY BLOCK
            if ($existingRole !== $roleName) {
                return response()->json([
                    'message' => "This email is already registered as a {$existingRoleDisplay}. The same email cannot be used to register for a different role ({$newRoleDisplay}). Please log in with your existing account or use another email.",
                    'code' => 'EMAIL_ALREADY_REGISTERED_DIFFERENT_ROLE',
                    'existing_role' => $existingRole,
                    'requested_role' => $roleName,
                ], 409);
            }

            // Case B: Same role requested, but account is already active, verified, or pending admin approval
            if ($existingUser->isEmailVerified() || $existingUser->isActive() || $existingUser->account_status === 'pending_admin_approval') {
                return response()->json([
                    'message' => "An account with this email is already registered as {$existingRoleDisplay}. Please login instead.",
                    'code' => 'EMAIL_ALREADY_REGISTERED',
                    'existing_role' => $existingRole,
                ], 409);
            }

            // Case C: Same role requested and still unverified -> resend verification OTP
            $existingUser->update([
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? $existingUser->phone,
            ]);

            $otp = $this->otpService->generateOtp($existingUser, $normalizedEmail);

            try {
                Mail::to($normalizedEmail)->send(new OtpEmail($otp, $existingUser->name));
            } catch (\Throwable $e) {
                Log::error("Failed to send registration OTP email to {$normalizedEmail}: " . $e->getMessage());
            }

            return response()->json([
                'message' => "An unverified {$existingRoleDisplay} account with this email was found. A new verification OTP has been sent.",
                'email' => $normalizedEmail,
                'role' => $existingRole,
                'status' => 'pending_email_verification',
                'continue_verification' => true
            ], 200);
        }

        // 2. Parse name
        $nameParts = explode(' ', trim($request->name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // 3. Create unverified user
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $normalizedEmail,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 'pending_email_verification',
            'account_status' => 'pending_email_verification',
            'admin_approved' => false,
            'email_verified_at' => null,
        ]);

        // 4. Role assignment
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web'
        ]);
        $user->assignRole($role);

        // 5. Auto-provision profile based on role
        match ($roleName) {
            'student' => $user->studentProfile()->create(),
            'expert' => $user->expertProfile()->create(['is_verified' => false, 'is_available' => true]),
            'company' => $user->companyProfile()->create(),
            'college' => $user->collegeProfile()->create(),
            'intern' => $user->internProfile()->create(),
            'job-seeker' => $user->jobSeekerProfile()->create(),
            default => $user->studentProfile()->create(),
        };

        // 6. Generate and send secure OTP
        $otp = $this->otpService->generateOtp($user, $normalizedEmail);

        try {
            Mail::to($normalizedEmail)->send(new OtpEmail($otp, $user->name));
        } catch (\Throwable $e) {
            Log::error("Failed to send registration OTP email to {$normalizedEmail}: " . $e->getMessage());
        }

        // 7. Record Audit Log
        AuditLogService::log($user->id, 'registration_created', [
            'email' => $normalizedEmail,
            'role' => $roleName,
            'requires_admin_approval' => $user->requiresAdminApproval()
        ]);

        // DO NOT issue an auth token here — user must verify email OTP first!
        return response()->json([
            'message' => 'Registration successful. Please verify your email with the 6-digit OTP sent.',
            'email' => $normalizedEmail,
            'role' => $roleName,
            'status' => 'pending_email_verification'
        ], 201);
    }

    /**
     * Verify email OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $normalizedEmail = strtolower(trim($request->email));
        $result = $this->otpService->verifyOtp($normalizedEmail, $request->otp);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'code' => $result['code'] ?? 'VERIFICATION_FAILED',
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ], 400);
        }

        $user = $result['user'] ?? User::where('email', $normalizedEmail)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Role-based activation rules
        if ($user->requiresAdminApproval()) {
            // Expert, College, Company: Must wait for Admin Approval
            $user->update([
                'account_status' => 'pending_admin_approval',
                'status' => 'pending_approval',
                'admin_approved' => false,
            ]);

            AuditLogService::log($user->id, 'email_verified_pending_approval', [
                'role' => $user->roles->first()?->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully. Your account is now waiting for administrator approval.',
                'status' => 'pending_admin_approval',
                'requires_approval' => true,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'status' => 'pending_admin_approval',
                ]
            ], 200);
        } else {
            // Student, Intern, Jobseeker: Auto-activate after email verification
            $user->update([
                'account_status' => 'active',
                'status' => 'active',
                'admin_approved' => true,
                'admin_approved_at' => now(),
            ]);

            AuditLogService::log($user->id, 'account_auto_activated', [
                'role' => $user->roles->first()?->name
            ]);

            $deviceInfo = $this->getDeviceInfo($request);
            $token = $user->createToken($deviceInfo['token_name'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully! Welcome to Sarvakshetra.',
                'status' => 'active',
                'token' => $token,
                'user' => $user->load('roles'),
            ], 200);
        }
    }

    /**
     * Resend verification OTP with 60-second cooldown enforcement.
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $normalizedEmail = strtolower(trim($request->email));
        $user = User::where('email', $normalizedEmail)->first();

        if (!$user) {
            return response()->json(['message' => 'No account found with this email address.'], 404);
        }

        if ($user->isEmailVerified() && $user->isActive()) {
            return response()->json([
                'message' => 'Your email is already verified. Please log in.',
                'code' => 'ALREADY_VERIFIED'
            ], 400);
        }

        // Check cooldown
        $cooldown = $this->otpService->getResendCooldownRemaining($normalizedEmail);
        if ($cooldown > 0) {
            return response()->json([
                'message' => "Please wait {$cooldown} seconds before requesting a new verification code.",
                'code' => 'COOLDOWN_ACTIVE',
                'cooldown_remaining' => $cooldown,
            ], 429);
        }

        // Generate and dispatch new OTP
        $otp = $this->otpService->generateOtp($user, $normalizedEmail);

        try {
            Mail::to($normalizedEmail)->send(new OtpEmail($otp, $user->name));
        } catch (\Throwable $e) {
            Log::error("Failed to resend OTP to {$normalizedEmail}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send email. Please try again shortly.'], 500);
        }

        AuditLogService::log($user->id, 'otp_resent', ['email' => $normalizedEmail]);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your registered email address.',
            'cooldown' => 60,
        ], 200);
    }

    /**
     * Authenticate user with server-side account state guards.
     */
    public function login(LoginRequest $request)
    {
        $throttleKey = 'login:' . strtolower(trim($request->email)) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Account locked due to too many failed login attempts for email: {$request->email} from IP: {$request->ip()}");
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300); // 5 minutes lockout
            Log::warning("Failed login attempt for email: {$request->email} from IP: {$request->ip()}");
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }
        
        RateLimiter::clear($throttleKey);

        // Admin and Super Admin users bypass status restrictions
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        if (!$isAdmin) {
            // 1. Email Verification Guard
            if (!$user->isEmailVerified() || $user->account_status === 'pending_email_verification') {
                return response()->json([
                    'message' => 'Please verify your email address before logging in.',
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'student'
                ], 403);
            }

            // 2. Suspension Guard
            if ($user->account_status === 'suspended' || $user->status === 'suspended') {
                return response()->json([
                    'message' => 'Your account is currently suspended. Please contact support.',
                    'code' => 'ACCOUNT_SUSPENDED'
                ], 403);
            }

            // 3. Rejection Guard
            if ($user->account_status === 'rejected' || $user->status === 'rejected') {
                $reasonMsg = $user->rejection_reason ? " Reason: {$user->rejection_reason}" : " Please check your email for details or contact support.";
                return response()->json([
                    'message' => 'Your account registration was not approved.' . $reasonMsg,
                    'code' => 'ACCOUNT_REJECTED',
                    'reason' => $user->rejection_reason
                ], 403);
            }

            // 4. Admin Approval Guard for Expert, College, Company
            if ($user->requiresAdminApproval()) {
                if (!$user->isAdminApproved() || $user->account_status === 'pending_admin_approval' || $user->status === 'pending_approval') {
                    return response()->json([
                        'message' => 'Your email has been verified, but your account is still awaiting administrator approval.',
                        'code' => 'PENDING_APPROVAL',
                        'role' => $user->roles->first()?->name
                    ], 403);
                }
            }

            // 5. Active Guard
            if (!$user->isActive()) {
                return response()->json([
                    'message' => 'Your account is currently inactive. Please contact support.',
                    'code' => 'ACCOUNT_INACTIVE'
                ], 403);
            }
        }

        // Generate token
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
        
        AuditLogService::log($user->id, 'user_login', [
            'device' => $deviceInfo['device'],
            'browser' => $deviceInfo['browser']
        ]);

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
        
        AuditLogService::log($user->id, 'password_changed');

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();
        
        $otp = rand(100000, 999999);
        Cache::put('password_reset_' . $user->email, $otp, now()->addMinutes(30));
        
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        try {
            Mail::to($user->email)->send(new PasswordResetMail($otp, $user->email, $userName));
        } catch (\Throwable $e) {
            Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'email' => $user->email,
            'message' => "A 6-digit OTP has been sent to your registered email address: {$user->email}"
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
        ]);

        $cachedOtp = Cache::get('password_reset_' . strtolower(trim($request->email)));

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        Cache::forget('password_reset_' . $user->email);
        $user->tokens()->delete();

        AuditLogService::log($user->id, 'password_reset_completed');

        return response()->json(['message' => 'Password reset successfully. Please log in with your new password.']);
    }

    public function socialLogin(Request $request)
    {
        return response()->json([
            'message' => 'Social login endpoint is future-ready.',
            'provider' => $request->provider
        ], 501);
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