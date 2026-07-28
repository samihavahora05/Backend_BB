@extends('emails.layout')

@section('content')
    <h2>We Received Your Message</h2>
    <p>Hello {{ $name }},</p>
    <p>Thank you for reaching out to BlueBoxx DA. We have received your inquiry/callback request:</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Subject:</strong> {{ $msgSubject ?? 'General Inquiry / Callback Request' }}</p>
        <p style="margin-bottom: 0;"><strong>Message/Details:</strong> {{ $msgDetails }}</p>
    </div>
    <p>Our support team will review your message and get back to you as soon as possible.</p>
@endsection
