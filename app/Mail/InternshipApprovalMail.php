<?php

namespace App\Mail;

use App\Models\InternshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternshipApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public InternshipApplication $application;
    public $letter;

    public function __construct(InternshipApplication $application, $letter = null)
    {
        $this->application = $application;
        $this->letter = $letter;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations! Your Internship Application Has Been Approved - ' . ($this->application->internship?->title ?? 'Blueboxx DA'),
        );
    }

    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url') ?: 'https://sarvakshetra.com', '/');
        $studentPortalUrl = $frontendUrl . '/login?redirect=/student/applications';

        return new Content(
            view: 'emails.internship_approved',
            with: [
                'app'              => $this->application,
                'studentPortalUrl' => $studentPortalUrl,
                'frontendUrl'      => $frontendUrl,
            ]
        );
    }
}