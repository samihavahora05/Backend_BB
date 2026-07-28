@extends('emails.layout')

@section('content')
    <h2>Scholarship Application Received</h2>
    <p>Thank you for applying for the scholarship program. We have successfully received your application.</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1e3a8a;">{{ $scholarshipName }}</h3>
        <p style="margin-bottom: 0;"><strong>Submission Date:</strong> {{ $submissionDate }}</p>
    </div>
    <p>Our review panel will evaluate your details and documents. You will receive an update once the evaluation process is complete.</p>
@endsection
