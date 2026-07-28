<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternshipApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $internshipTitle;
    public $companyName;
    public $appliedDate;
    public $status;

    public function __construct($internshipTitle, $companyName, $appliedDate, $status)
    {
        $this->internshipTitle = $internshipTitle;
        $this->companyName = $companyName;
        $this->appliedDate = $appliedDate;
        $this->status = $status;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Internship Application Received: ' . $this->internshipTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.internship_application',
        );
    }
}
