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
        return new Envelope(
            subject: 'Update Regarding Your Internship Application - ' . ($this->application->internship?->title ?? 'Blueboxx DA'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.internship_rejected',
            with: [
                'app'    => $this->application,
                'reason' => $this->reason,
            ]
        );
    }
}