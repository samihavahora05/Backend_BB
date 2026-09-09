<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #1f2937; margin: 0; padding: 24px; font-size: 14px; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        p { margin: 0 0 16px 0; }
        .btn { display: inline-block; background-color: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 10px 22px; border-radius: 4px; font-size: 14px; font-weight: 600; margin: 16px 0; }
        .signature { margin-top: 28px; font-size: 14px; color: #374151; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <p>Hi {{ $user->first_name }},</p>

        <p>Your BlueBoxx account registration has been formally approved by our administration team.</p>

        <p>You can now log in to your account and access your dashboard using the button below:</p>

        <div>
            <a href="{{ config('app.frontend_url') ?? 'https://sarvakshetra.com' }}/login" class="btn" target="_blank">
                Log In to Your Account
            </a>
        </div>

        <div class="signature">
            Regards,<br><br>
            <strong>Operations &amp; Admin Team</strong><br>
            BlueBoxx Designs &amp; Animation Pvt. Ltd.<br>
            <a href="{{ config('app.frontend_url') ?? 'https://sarvakshetra.com' }}" style="color: #2563eb; text-decoration: none;">sarvakshetra.com</a>
        </div>
    </div>
</body>
</html>
