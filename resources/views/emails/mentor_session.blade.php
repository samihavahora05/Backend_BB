@extends('emails.layout')

@section('content')
    <h2>Mentor Session Confirmed</h2>
    <p>Your mentor session booking has been successfully confirmed:</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1e3a8a;">Session with {{ $mentorName }}</h3>
        <p><strong>Topic / Details:</strong> {{ $sessionTopic }}</p>
        <p style="margin-bottom: 0;"><strong>Scheduled Time:</strong> {{ $sessionTime }}</p>
    </div>
    <p>Please join the session on time. You can view session details and links inside your dashboard.</p>
@endsection
