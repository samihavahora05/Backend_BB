@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background: #EFF6FF; color: #1E3A8A; font-size: 24px; font-weight: bold; margin-bottom: 12px; border: 1px solid #DBEAFE;">
            🔒
        </div>
        <h2 style="color: #0F172A; margin: 0 0 8px 0; font-size: 22px; font-weight: 800; font-family: 'Segoe UI', system-ui, sans-serif;">Password Reset Verification</h2>
        <p style="color: #64748B; font-size: 14px; margin: 0;">Use the verification code below to securely reset your BlueBoxx account password.</p>
    </div>

    @if(!empty($userName))
        <p style="font-size: 14px; color: #334155; margin-bottom: 12px;">Hello <strong>{{ $userName }}</strong>,</p>
    @else
        <p style="font-size: 14px; color: #334155; margin-bottom: 12px;">Hello,</p>
    @endif

    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
        We received a request to reset the password for your BlueBoxx account associated with registered email 
        <strong style="color: #1E3A8A;">{{ $userEmail ?? 'your registered email address' }}</strong>.
    </p>

    <div style="background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%); border: 1.5px dashed #3B82F6; border-radius: 12px; padding: 24px; text-align: center; margin: 24px 0;">
        <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">
            Your One-Time Password (OTP)
        </div>
        <div style="font-size: 36px; font-weight: 900; letter-spacing: 0.25em; color: #1E3A8A; font-family: 'Courier New', monospace; padding: 8px 0;">
            {{ $otp }}
        </div>
        <div style="font-size: 12px; color: #DC2626; font-weight: 600; margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 4px;">
            ⏱ This code will expire in <strong>30 minutes</strong>.
        </div>
    </div>

    <div style="background: #FFFBEB; border-left: 4px solid #F59E0B; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px;">
        <p style="font-size: 12px; color: #92400E; margin: 0; line-height: 1.5;">
            <strong>Security Notice:</strong> Never share this OTP with anyone. BlueBoxx staff will never ask for your verification code or password.
        </p>
    </div>

    <p style="font-size: 13px; color: #64748B; line-height: 1.5; margin-top: 24px;">
        If you did not request this password reset, you can safely disregard this email. Your password will remain unchanged and your account is secure.
    </p>
@endsection
