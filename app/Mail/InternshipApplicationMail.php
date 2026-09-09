<?php

namespace App\Mail;

use App\Models\InternshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InternshipApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?InternshipApplication $application = null;
    public string $applicantName;
    public string $internshipTitle;
    public string $companyName;
    public string $appliedDate;
    public string $status;
    public string $termsVersion;

    public function __construct($applicationOrTitle, $companyName = 'BlueBoxx DA', $appliedDate = null, $status = 'Applied')
    {
        if ($applicationOrTitle instanceof InternshipApplication) {
            $this->application = $applicationOrTitle;
            $this->applicantName = $applicationOrTitle->applicant_name ?: ($applicationOrTitle->first_name . ' ' . $applicationOrTitle->last_name);
            $this->internshipTitle = $applicationOrTitle->internship?->title ?? $applicationOrTitle->application_type ?? 'Internship Program';
            $this->companyName = $applicationOrTitle->internship?->company_name ?? 'BlueBoxx DA PVT. LTD.';
            $this->appliedDate = $applicationOrTitle->applied_at ? $applicationOrTitle->applied_at->format('M d, Y • h:i A') : now()->format('M d, Y • h:i A');
            $this->status = ucfirst($applicationOrTitle->status ?? 'Applied');
            $this->termsVersion = $applicationOrTitle->terms_version ?? 'v1.0';
        } else {
            $this->applicantName = 'Applicant';
            $this->internshipTitle = (string) $applicationOrTitle;
            $this->companyName = (string) $companyName;
            $this->appliedDate = $appliedDate ? (is_string($appliedDate) ? $appliedDate : $appliedDate->format('M d, Y')) : now()->format('M d, Y');
            $this->status = ucfirst((string) $status);
            $this->termsVersion = 'v1.0';
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Confirmation: ' . $this->internshipTitle . ' | BlueBoxx DA',
        );
    }

    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url') ?: 'https://sarvakshetra.com', '/');
        $studentPortalUrl = $frontendUrl . '/login?redirect=/student/applications';

        return new Content(
            view: 'emails.internship_application',
            with: [
                'app'              => $this->application,
                'applicantName'    => $this->applicantName,
                'internshipTitle'  => $this->internshipTitle,
                'companyName'      => $this->companyName,
                'appliedDate'      => $this->appliedDate,
                'status'           => $this->status,
                'termsVersion'     => $this->termsVersion,
                'studentPortalUrl' => $studentPortalUrl,
                'frontendUrl'      => $frontendUrl,
            ]
        );
    }
}
