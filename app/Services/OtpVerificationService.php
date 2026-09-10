<?php

namespace App\Services;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpVerificationService
{
    const OTP_EXPIRY_MINUTES = 10;
    const MAX_ATTEMPTS = 5;
    const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate a new cryptographically secure 6-digit OTP and store hashed in DB.
     */
    public function generateOtp(?User $user, string $email): string
    {
        $normalizedEmail = strtolower(trim($email));

        // 1. Invalidate any existing unverified OTPs for this email
        EmailVerificationOtp::where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->delete();

        // 2. Cryptographically secure 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // 3. Store hashed OTP in database
        EmailVerificationOtp::create([
            'user_id' => $user?->id,
            'email' => $normalizedEmail,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        // 4. Set resend cooldown in cache
        Cache::put('otp_cooldown_' . $normalizedEmail, true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS));

        // 5. Audit log
        AuditLogService::log(
            $user?->id,
            'otp_generated',
            [
                'email' => $normalizedEmail,
                'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->toIso8601String(),
            ]
        );

        return $otp;
    }

    /**
     * Verify an OTP provided by the user.
     */
    public function verifyOtp(string $email, string $otp): array
    {
        $normalizedEmail = strtolower(trim($email));

        $otpRecord = EmailVerificationOtp::where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'No active verification code found for this email. Please request a new OTP.',
                'code' => 'OTP_NOT_FOUND',
            ];
        }

        // Check if expired
        if ($otpRecord->isExpired()) {
            AuditLogService::log($otpRecord->user_id, 'otp_expired', ['email' => $normalizedEmail]);
            return [
                'success' => false,
                'message' => 'This verification code has expired. Please request a new OTP.',
                'code' => 'OTP_EXPIRED',
            ];
        }

        // Check max attempts
        if ($otpRecord->hasExceededMaxAttempts(self::MAX_ATTEMPTS)) {
            $otpRecord->delete(); // Invalidate
            AuditLogService::log($otpRecord->user_id, 'otp_blocked_max_attempts', ['email' => $normalizedEmail]);
            return [
                'success' => false,
                'message' => 'Too many failed verification attempts. This code has been invalidated. Please request a new OTP.',
                'code' => 'MAX_ATTEMPTS_EXCEEDED',
            ];
        }

        // Verify OTP hash
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $otpRecord->attempts;

            AuditLogService::log($otpRecord->user_id, 'otp_failed', [
                'email' => $normalizedEmail,
                'attempt' => $otpRecord->attempts,
                'remaining' => max(0, $remaining),
            ]);

            if ($remaining <= 0) {
                $otpRecord->delete();
                return [
                    'success' => false,
                    'message' => 'Incorrect verification code. Maximum attempts reached. Please request a new OTP.',
                    'code' => 'MAX_ATTEMPTS_EXCEEDED',
                ];
            }

            return [
                'success' => false,
                'message' => "Invalid verification code. {$remaining} attempt(s) remaining.",
                'code' => 'INVALID_OTP',
                'remaining_attempts' => $remaining,
            ];
        }

        // OTP is correct! Mark verified
        $otpRecord->update([
            'verified_at' => now(),
        ]);

        // Find and update user's email_verified_at
        $user = User::where('email', $normalizedEmail)->first();
        if ($user) {
            $user->update([
                'email_verified_at' => now(),
            ]);
        }

        AuditLogService::log($user?->id, 'otp_verified', ['email' => $normalizedEmail]);

        return [
            'success' => true,
            'message' => 'Email verified successfully.',
            'user' => $user,
        ];
    }

    /**
     * Check if user is in resend cooldown.
     */
    public function getResendCooldownRemaining(string $email): int
    {
        $normalizedEmail = strtolower(trim($email));
        $key = 'otp_cooldown_' . $normalizedEmail;
        
        if (!Cache::has($key)) {
            return 0;
        }

        $latest = EmailVerificationOtp::where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($latest) {
            $secondsSinceCreation = now()->diffInSeconds($latest->created_at);
            $remaining = self::RESEND_COOLDOWN_SECONDS - $secondsSinceCreation;
            return max(0, $remaining);
        }

        return 0;
    }
}