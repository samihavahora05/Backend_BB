@extends('emails.layout')

@section('content')
    <p>Dear Student,</p>

    <p>You have successfully enrolled in the following course:</p>

    <div class="details-box">
        <p style="margin: 0 0 6px 0;"><strong>Course Name:</strong> {{ $courseName }}</p>
        <p style="margin: 0;"><strong>Enrolled On:</strong> {{ $enrollmentDate }}</p>
    </div>

    <p>You can access your modules and start learning from your course dashboard:</p>

    <div>
        <a href="{{ $courseUrl }}" class="btn" target="_blank">Access Course</a>
    </div>

    <div class="signature">
        Regards,<br><br>
        <strong>Academic &amp; Learning Operations</strong><br>
        BlueBoxx Designs &amp; Animation Pvt. Ltd.
    </div>
@endsection
