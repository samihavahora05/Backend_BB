<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContestRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contestName;
    public $scheduledDate;

    public function __construct($contestName, $scheduledDate)
    {
        $this->contestName = $contestName;
        $this->scheduledDate = $scheduledDate;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contest Registration Confirmed: ' . $this->contestName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contest_registration',
        );
    }
}
