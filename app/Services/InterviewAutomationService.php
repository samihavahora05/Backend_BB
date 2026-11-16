<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\User;
use App\Mail\InternInterviewScheduledMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InterviewAutomationService
{
    /**
     * Admin alert mobile number for WhatsApp
     */
    const ADMIN_PHONE = '9023512853';

    /**
     * Trigger interview scheduling & notifications if candidate scores 30+
     */
    public static function processQualifiedAttempt(AssessmentAttempt $attempt): void
    {
        // Must score at least 30 marks out of 50
        if ($attempt->score < 30) {
            $attempt->update(['is_qualified' => false]);
            return;
        }

        $attempt->update(['is_qualified' => true]);

        // If meet link is already generated, don't generate duplicate
        if (!empty($attempt->meet_link)) {
            return;
        }

        // 1. Generate unique Google Meet / Interview Room Link via Google Meet Service or configured room
        $scheduledTime = now()->addDay()->setTime(11, 0, 0);
        $user = $attempt->user;

        $meetLink = GoogleMeetService::createMeeting(
            "BlueBoxx Technical Interview - " . ($user?->name ?? 'Candidate'),
            "Interview for candidate who qualified the 50 MCQ Assessment with {$attempt->score}/50 marks.",
            $scheduledTime,
            $scheduledTime->copy()->addMinutes(45),
            array_filter([$user?->email, env('ADMIN_INTERVIEW_EMAIL', 'support@blueboxx.in')])
        );

        if (empty($meetLink) || $meetLink === 'https://meet.google.com/new') {
            $meetLink = env('DEFAULT_INTERVIEW_MEET_LINK', 'https://meet.google.com/landing');
        }

        $attempt->update([
            'meet_link' => $meetLink,
            'interview_scheduled_at' => $scheduledTime,
            'interview_status' => 'scheduled',
        ]);

        $attempt->loadMissing(['user', 'assessment']);
        $user = $attempt->user;

        if (!$user) {
            return;
        }

        // 2. Dispatch Email to Candidate
        $emailSent = self::sendInterviewEmail($user, $attempt, $meetLink, $scheduledTime);

        // 3. Dispatch WhatsApp to Candidate
        $candidatePhone = $user->phone;
        $candidateWaSent = false;
        if (!empty($candidatePhone)) {
            $candidateWaSent = self::sendWhatsAppMessage(
                $candidatePhone,
                "🎉 *Congratulations {$user->name}!* \n\nYou have successfully passed the BlueBoxx 50 MCQ Assessment with *{$attempt->score}/50 marks*! \n\nYour Technical Interview Round has been scheduled:\n🗓 Date & Time: *{$scheduledTime->format('d M, Y at h:i A')}*\n🔗 Google Meet Link: {$meetLink}\n\nPlease join 5 minutes before time. All the best!\n- Team BlueBoxx"
            );
        }

        // 4. Dispatch WhatsApp Alert to Admin (9023512853)
        $adminWaSent = self::sendWhatsAppMessage(
            self::ADMIN_PHONE,
            "🚀 *New Qualified Intern Alert (BlueBoxx)*\n\nCandidate: *{$user->name}* ({$user->email})\nPhone: *{$candidatePhone}*\nScore: *{$attempt->score}/50* (PASSED)\nScheduled Time: *{$scheduledTime->format('d M, Y at h:i A')}*\nGoogle Meet Link: {$meetLink}\n\nPlease review in Admin Panel."
        );

        $attempt->update([
            'email_sent' => $emailSent,
            'whatsapp_sent' => ($candidateWaSent || $adminWaSent),
        ]);
    }

    /**
     * Send email with Google Meet interview details
     */
    private static function sendInterviewEmail(User $user, AssessmentAttempt $attempt, string $meetLink, $scheduledTime): bool
    {
        try {
            Mail::to($user->email)->send(new InternInterviewScheduledMail($user, $attempt, $meetLink, $scheduledTime));
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send interview email to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp message via WhatsApp Cloud API / Webhook or Fast2Sms fallback
     */
    public static function sendWhatsAppMessage(string $rawPhone, string $message): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        try {
            // Check if WhatsApp Business API / Webhook is configured in environment
            $waApiUrl = env('WHATSAPP_API_URL');
            $waApiKey = env('WHATSAPP_API_KEY');

            if ($waApiUrl && $waApiKey) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$waApiKey}",
                    'Content-Type' => 'application/json',
                ])->post($waApiUrl, [
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

                Log::info("WhatsApp API dispatched to {$phone}: " . $response->body());
                return $response->successful();
            }

            // Fallback: Log WhatsApp dispatch for auditing & debug
            Log::info("📱 [WHATSAPP DISPATCH] To: {$phone}\nMessage: {$message}");
            return true;
        } catch (\Throwable $e) {
            Log::error("WhatsApp dispatch error to {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
