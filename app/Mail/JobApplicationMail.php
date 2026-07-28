<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $jobTitle;
    public $companyName;
    public $appliedDate;
    public $status;

    public function __construct($jobTitle, $companyName, $appliedDate, $status)
    {
        $this->jobTitle = $jobTitle;
        $this->companyName = $companyName;
        $this->appliedDate = $appliedDate;
        $this->status = $status;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Job Application Received: ' . $this->jobTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job_application',
        );
    }
}
