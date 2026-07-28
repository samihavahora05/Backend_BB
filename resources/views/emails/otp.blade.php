@extends('emails.layout')

@section('content')
    <h2>One-Time Password (OTP) Verification</h2>
    <p>Please use the following verification code to secure your login or registration request:</p>
    <div style="text-align: center;">
        <span class="otp-code">{{ $otp }}</span>
    </div>
    <p>This OTP is valid for <strong>10 minutes</strong>. For security reasons, please do not share this OTP with anyone.</p>
    <p>If you did not request this, please change your password immediately.</p>
@endsection
