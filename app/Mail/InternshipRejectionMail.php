<?php

namespace App\Mail;

use App\Models\InternshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternshipRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public InternshipApplication $application;
    public ?string $reason;

    public function __construct(InternshipApplication $application, ?string $reason = null)
    {
        $this->application = $application;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        $position = $this->application->internship?->title ?? $this->application->application_type ?? 'Internship';
        return new Envelope(
            subject: "Application Status Update – {$position}",
        );
    }

    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url') ?: 'https://sarvakshetra.com', '/');

        return new Content(
            view: 'emails.internship_rejected',
            with: [
                'app'         => $this->application,
                'position'    => $this->application->internship?->title ?? $this->application->application_type ?? 'Internship Position',
                'reason'      => $this->reason,
                'frontendUrl' => $frontendUrl,
            ]
        );
    }
}