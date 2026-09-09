@extends('emails.layout')

@section('content')
    <p>Dear Applicant,</p>

    <p>Your job application has been successfully submitted and logged in our system.</p>

    <div class="details-box">
        <p style="margin: 0 0 6px 0;"><strong>Position:</strong> {{ $jobTitle }}</p>
        <p style="margin: 0 0 6px 0;"><strong>Company:</strong> {{ $companyName }}</p>
        <p style="margin: 0 0 6px 0;"><strong>Applied Date:</strong> {{ $appliedDate }}</p>
        <p style="margin: 0;"><strong>Status:</strong> {{ ucfirst($status) }}</p>
    </div>

    <p>The hiring team will review your credentials and reach out regarding next steps if shortlisted.</p>

    <div class="signature">
        Regards,<br><br>
        <strong>Talent Acquisition Team</strong><br>
        BlueBoxx Designs &amp; Animation Pvt. Ltd.
    </div>
@endsection
