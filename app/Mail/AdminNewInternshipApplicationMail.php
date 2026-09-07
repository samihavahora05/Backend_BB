<?php

namespace App\Mail;

use App\Models\InternshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewInternshipApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public InternshipApplication $application;

    public function __construct(InternshipApplication $application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Internship Application Received: #' . $this->application->id . ' - ' . $this->application->applicant_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_new_internship_application',
            with: [
                'app' => $this->application,
            ]
        );
    }
}