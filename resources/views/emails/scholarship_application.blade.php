@extends('emails.layout')

@section('content')
    <p>Dear Applicant,</p>

    <p>Your application for the scholarship program has been received and registered for evaluation.</p>

    <div class="details-box">
        <p style="margin: 0 0 6px 0;"><strong>Program:</strong> {{ $scholarshipName }}</p>
        <p style="margin: 0;"><strong>Submission Date:</strong> {{ $submissionDate }}</p>
    </div>

    <p>Our scholarship review committee will assess your academic records and application details. You will receive an official notification once the review is completed.</p>

    <div class="signature">
        Regards,<br><br>
        <strong>Scholarship Review Committee</strong><br>
        BlueBoxx Designs &amp; Animation Pvt. Ltd.
    </div>
@endsection
