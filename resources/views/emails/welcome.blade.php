@extends('emails.layout')

@section('content')
    <p>Hi {{ $name }},</p>

    <p>Welcome to <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong> Your account has been registered under the role of <strong>{{ ucfirst($role) }}</strong>.</p>

    <p>You can access your learning paths, internship tracking, and career tools from your portal:</p>

    <div>
        <a href="{{ $loginUrl ?? (config('app.frontend_url') ?? 'https://sarvakshetra.com') . '/login' }}" class="btn" target="_blank">
            Log In to Your Dashboard
        </a>
    </div>

    <div class="signature">
        Regards,<br><br>
        <strong>Student Onboarding &amp; Support Team</strong><br>
        BlueBoxx Designs &amp; Animation Pvt. Ltd.
    </div>
@endsection
