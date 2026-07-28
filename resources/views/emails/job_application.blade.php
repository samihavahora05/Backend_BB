@extends('emails.layout')

@section('content')
    <h2>Job Application Submitted</h2>
    <p>Your application for the following position has been received:</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1e3a8a;">{{ $jobTitle }}</h3>
        <p><strong>Company:</strong> {{ $companyName }}</p>
        <p><strong>Applied Date:</strong> {{ $appliedDate }}</p>
        <p style="margin-bottom: 0;"><strong>Status:</strong> <span style="background-color: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-weight: 600;">{{ ucfirst($status) }}</span></p>
    </div>
    <p>The company's hiring team will review your application. We will notify you of any updates.</p>
@endsection
