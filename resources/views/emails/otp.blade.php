@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #1B2A6B; margin: 0 0 8px 0;">Verify Your Sarvakshetra Account</h2>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Secure one-time verification code</p>
    </div>

    <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">Hello <strong>{{ $name ?? 'User' }}</strong>,</p>
    
    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px;">
        Thank you for registering with Sarvakshetra. To complete your account verification, please enter the following 6-digit One-Time Password (OTP):
    </p>

    <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; margin: 24px 0;">
        <span style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: 900; letter-spacing: 8px; color: #1B2A6B; display: inline-block;">{{ $otp }}</span>
    </div>

    <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; margin: 20px 0;">
        <p style="font-size: 13px; color: #92400e; margin: 0;">
            ⏳ <strong>This OTP is valid for 10 minutes.</strong> Do not share this OTP with anyone.
        </p>
    </div>

    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-top: 24px;">
        If you did not initiate this registration request, you can safely ignore this email.
    </p>

    <div class="signature">
        <p style="margin: 0; font-weight: 700; color: #1B2A6B;">Sarvakshetra Team</p>
        <p style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">Empowering Careers & Future-Ready Learning</p>
    </div>
@endsection