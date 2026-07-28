@extends('emails.layout')

@section('content')
    <h2>Password Reset Request</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <div style="text-align: center;">
        <span class="otp-code">{{ $otp }}</span>
    </div>
    <p>Please enter this code on the reset page to set a new password. The code will expire in <strong>15 minutes</strong>.</p>
    <p>If you did not request a password reset, no further action is required.</p>
@endsection
