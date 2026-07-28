@extends('emails.layout')

@section('content')
    <h2>Course Enrollment Confirmation</h2>
    <p>Congratulations! You have successfully enrolled in the course:</p>
    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1e3a8a;">{{ $courseName }}</h3>
        <p style="margin-bottom: 0;"><strong>Enrolled Date:</strong> {{ $enrollmentDate }}</p>
    </div>
    <p>You can now access the course material and start learning.</p>
    <div style="text-align: center;">
        <a href="{{ $courseUrl }}" class="btn">Go to Course</a>
    </div>
@endsection
