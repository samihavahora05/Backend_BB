@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-block; width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; line-height: 48px; font-size: 24px; color: #10b981; margin-bottom: 12px;">✓</div>
        <h2 style="font-size: 20px; font-weight: 800; color: #1B2A6B; margin: 0 0 8px 0;">Account Approved!</h2>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Your Sarvakshetra account is now active</p>
    </div>

    <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">Hello <strong>{{ $user->name }}</strong>,</p>
    
    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
        Great news! Your <strong>{{ $roleName }}</strong> registration on Sarvakshetra has been reviewed and approved by our administration team.
    </p>

    <div class="details-box">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <tr>
                <td style="padding: 6px 0; color: #64748b; width: 140px;">Registered Name:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #1e293b;">{{ $user->name }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #64748b;">Registered Email:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #1e293b;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #64748b;">Account Type:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #1B2A6B;">{{ $roleName }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #64748b;">Account Status:</td>
                <td style="padding: 6px 0; font-weight: 700; color: #10b981;">Active ✓</td>
            </tr>
        </table>
    </div>

    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 20px 0 24px 0;">
        You can now log in using your registered email address and password to access your dashboard.
    </p>

    <div style="text-align: center; margin: 28px 0;">
        <a href="https://sarvakshetra.com/login" class="btn" style="background-color: #1B2A6B; border-radius: 8px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 28px;">Login to Your Dashboard</a>
    </div>

    <div class="signature">
        <p style="margin: 0; font-weight: 700; color: #1B2A6B;">Sarvakshetra Administration</p>
        <p style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">Empowering Careers & Future-Ready Learning</p>
    </div>
@endsection