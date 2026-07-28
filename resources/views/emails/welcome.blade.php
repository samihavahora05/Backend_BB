@extends('emails.layout')

@section('content')
    <h2>Welcome to BlueBoxx DA, {{ $name }}!</h2>
    <p>We are excited to have you join our platform as a <strong>{{ ucfirst($role) }}</strong>.</p>
    <p>BlueBoxx DA helps you master skills, connect with industry mentors, and discover career opportunities.</p>
    <div style="text-align: center;">
        <a href="{{ $loginUrl ?? '#' }}" class="btn">Login to Your Account</a>
    </div>
    <p style="margin-top: 30px;">Best regards,<br>The BlueBoxx DA Team</p>
@endsection
