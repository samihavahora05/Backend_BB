@extends('emails.layout')

@section('content')
    <h2>Contest Registration Confirmed</h2>
    <p>Your registration for the following contest has been confirmed:</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1e3a8a;">{{ $contestName }}</h3>
        <p><strong>Scheduled On:</strong> {{ $scheduledDate }}</p>
    </div>
    <p>Please make sure you read the contest guidelines and log in on time to participate.</p>
@endsection
