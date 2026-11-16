<?php

namespace App\Mail;

use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternInterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public AssessmentAttempt $attempt;
    public string $meetLink;
    public $scheduledTime;

    public function __construct(User $user, AssessmentAttempt $attempt, string $meetLink, $scheduledTime)
    {
        $this->user = $user;
        $this->attempt = $attempt;
        $this->meetLink = $meetLink;
        $this->scheduledTime = $scheduledTime;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations! Your BlueBoxx Technical Interview is Scheduled',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.intern_interview_scheduled',
        );
    }
}
