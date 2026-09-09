<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status Update</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #1f2937; font-size: 15px; line-height: 1.6; margin: 0; padding: 24px;">
    <div style="max-width: 580px; margin: 0 auto; background-color: #ffffff;">
        <p style="margin: 0 0 16px 0; color: #1f2937;">Dear {{ $app->applicant_name }},</p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            Thank you for taking the time to apply for the <strong>{{ $position ?? ($app->internship?->title ?? $app->application_type ?? 'Internship') }}</strong> opportunity with BlueBoxx Designs &amp; Animation Pvt. Ltd.
        </p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            After careful review of all submissions for this cohort, we regret to inform you that we are unable to move forward with your application at this time.
        </p>

        @if(!empty($reason))
        <p style="margin: 0 0 16px 0; color: #4b5563; font-style: italic;">
            <strong>Feedback:</strong> {{ $reason }}
        </p>
        @endif

        <p style="margin: 0 0 20px 0; color: #1f2937;">
            We encourage you to continue developing your skills and explore future openings on our careers platform. We wish you every success in your academic and professional journey.
        </p>

        <p style="margin: 28px 0 0 0; color: #374151; font-size: 14px; line-height: 1.5; border-top: 1px solid #e5e7eb; padding-top: 16px;">
            Regards,<br><br>
            <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
            <a href="mailto:info.blueboxx@gmail.com" style="color: #2563eb; text-decoration: none;">info.blueboxx@gmail.com</a><br>
            <a href="{{ $frontendUrl ?? 'https://sarvakshetra.com' }}" style="color: #2563eb; text-decoration: none;">sarvakshetra.com</a>
        </p>
    </div>
</body>
</html>