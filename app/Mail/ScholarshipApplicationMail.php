<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScholarshipApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $scholarshipName;
    public $submissionDate;

    public function __construct($scholarshipName, $submissionDate)
    {
        $this->scholarshipName = $scholarshipName;
        $this->submissionDate = $submissionDate;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scholarship Application Received: ' . $this->scholarshipName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scholarship_application',
        );
    }
}
