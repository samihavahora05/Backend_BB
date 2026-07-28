<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseEnrollmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $courseName;
    public $enrollmentDate;
    public $courseUrl;

    public function __construct($courseName, $enrollmentDate, $courseUrl)
    {
        $this->courseName = $courseName;
        $this->enrollmentDate = $enrollmentDate;
        $this->courseUrl = $courseUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Successfully Enrolled in ' . $this->courseName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_enrollment',
        );
    }
}
