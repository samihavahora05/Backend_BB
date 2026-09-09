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
        $studentName = $this->application->applicant_name ?? 'Applicant';
        $position = $this->application->internship?->title ?? $this->application->application_type ?? 'Internship';

        return new Envelope(
            subject: "New Internship Application – {$studentName} – {$position}",
        );
    }

    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url') ?: 'https://sarvakshetra.com', '/');
        $adminReviewUrl = $frontendUrl . '/login?redirect=/admin/internships/applications';

        return new Content(
            view: 'emails.admin_new_internship_application',
            with: [
                'app'            => $this->application,
                'adminReviewUrl' => $adminReviewUrl,
                'frontendUrl'    => $frontendUrl,
            ]
        );
    }
}