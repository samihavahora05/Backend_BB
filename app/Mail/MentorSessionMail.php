<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MentorSessionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mentorName;
    public $sessionTopic;
    public $sessionTime;

    public function __construct($mentorName, $sessionTopic, $sessionTime)
    {
        $this->mentorName = $mentorName;
        $this->sessionTopic = $sessionTopic;
        $this->sessionTime = $sessionTime;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mentor Session Booking Confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mentor_session',
        );
    }
}
