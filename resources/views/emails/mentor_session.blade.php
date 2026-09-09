@extends('emails.layout')

@section('content')
    <p>Dear Student,</p>

    <p>Your mentor session booking has been confirmed:</p>

    <div class="details-box">
        <p style="margin: 0 0 6px 0;"><strong>Mentor:</strong> {{ $mentorName }}</p>
        <p style="margin: 0 0 6px 0;"><strong>Topic:</strong> {{ $sessionTopic }}</p>
        <p style="margin: 0;"><strong>Scheduled Time:</strong> {{ $sessionTime }}</p>
    </div>

    <p>Please be prepared and join the session on time. You can access meeting links and preparation materials from your student dashboard.</p>

    <div class="signature">
        Regards,<br><br>
        <strong>Mentorship &amp; Industry Network Team</strong><br>
        BlueBoxx Designs &amp; Animation Pvt. Ltd.
    </div>
@endsection
