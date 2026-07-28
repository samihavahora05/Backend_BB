<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $msgSubject;
    public $msgDetails;

    public function __construct($name, $msgSubject, $msgDetails)
    {
        $this->name = $name;
        $this->msgSubject = $msgSubject;
        $this->msgDetails = $msgDetails;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We Received Your Callback/Contact Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_form',
        );
    }
}
