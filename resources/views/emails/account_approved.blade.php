<!DOCTYPE html>
<html>
<head>
    <title>Account Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 8px; text-align: center;">
        <h2 style="color: #1B2A6B;">Your Account is Approved!</h2>
        <p>Hello {{ $user->first_name }},</p>
        <p>Great news! Your account on BlueBoxx DA has been officially approved by our Admin team.</p>
        <p>You can now log in and access all the features available to your role.</p>
        <a href="{{ config('app.frontend_url') ?? 'http://localhost:3000' }}/login" style="display: inline-block; padding: 10px 20px; margin-top: 20px; background-color: #C9A227; color: #fff; text-decoration: none; font-weight: bold; border-radius: 5px;">Log In Now</a>
        <p style="margin-top: 30px; font-size: 12px; color: #888;">Thank you for joining BlueBoxx DA!</p>
    </div>
</body>
</html>
