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
        $position = $this->application->internship?->title ?? $this->application->application_type ?? 'Internship';
        return new Envelope(
            subject: "Application Approved – {$position}",
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
                'position'         => $this->application->internship?->title ?? $this->application->application_type ?? 'Internship Position',
                'studentName'      => $this->application->applicant_name ?? 'Student',
                'referenceId'      => $this->application->reference_id ?? ('BB-INT-' . str_pad($this->application->id, 5, '0', STR_PAD_LEFT)),
                'approvedDate'     => $this->application->updated_at ? $this->application->updated_at->format('M d, Y') : now()->format('M d, Y'),
                'studentPortalUrl' => $studentPortalUrl,
                'frontendUrl'      => $frontendUrl,
            ]
        );
    }
}