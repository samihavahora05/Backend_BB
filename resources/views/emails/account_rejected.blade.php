@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #dc2626; margin: 0 0 8px 0;">Account Application Update</h2>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Information regarding your Sarvakshetra registration</p>
    </div>

    <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">Hello <strong>{{ $user->name }}</strong>,</p>
    
    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px;">
        Thank you for your interest in joining Sarvakshetra. Our administration team has reviewed your <strong>{{ $roleName }}</strong> application.
    </p>

    <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; padding: 16px; margin: 20px 0;">
        <p style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #991b1b; margin: 0 0 6px 0;">Reason for Decision:</p>
        <p style="font-size: 14px; color: #7f1d1d; line-height: 1.5; margin: 0;">{{ $reason }}</p>
    </div>

    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 20px 0;">
        If you have any questions or would like to submit updated credentials, please contact our support team.
    </p>

    <div style="text-align: center; margin: 28px 0;">
        <a href="https://sarvakshetra.com/contact" class="btn" style="background-color: #1B2A6B; border-radius: 8px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 28px;">Contact Support</a>
    </div>

    <div class="signature">
        <p style="margin: 0; font-weight: 700; color: #1B2A6B;">Sarvakshetra Administration</p>
        <p style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">Empowering Careers & Future-Ready Learning</p>
    </div>
@endsection