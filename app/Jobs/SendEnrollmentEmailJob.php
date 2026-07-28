<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Course;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendEnrollmentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public User $user,
        public Course $course,
    ) {}

    public function handle(): void
    {
        $subject = "🎉 You're enrolled in: " . $this->course->title;

        $log = EmailLog::create([
            'recipient'      => $this->user->email,
            'subject'        => $subject,
            'mailable_class' => 'EnrollmentConfirmation',
            'status'         => 'pending',
        ]);

        try {
            Mail::send([], [], function ($mail) use ($subject) {
                $studentName = trim($this->user->first_name . ' ' . $this->user->last_name);
                $courseTitle = $this->course->title;

                $mail->to($this->user->email, $studentName)
                    ->subject($subject)
                    ->html("
                        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;'>
                            <h2 style='color: #1B2A6B;'>Congratulations, {$studentName}! 🎉</h2>
                            <p>You have successfully enrolled in <strong>{$courseTitle}</strong>.</p>
                            <p>You can start learning right away from your Student Dashboard.</p>
                            <br>
                            <a href='" . config('app.url') . "/student/courses'
                               style='background:#1B2A6B; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; display:inline-block;'>
                               Go to My Courses
                            </a>
                            <br><br>
                            <p style='color:#888; font-size:12px;'>— The BlueBoxx DA Team</p>
                        </div>
                    ");
            });

            $log->update(['status' => 'sent']);
        } catch (Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
