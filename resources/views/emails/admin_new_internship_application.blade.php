<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Internship Application</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #1f2937; font-size: 15px; line-height: 1.6; margin: 0; padding: 24px;">
    <div style="max-width: 580px; margin: 0 auto; background-color: #ffffff;">
        <p style="margin: 0 0 16px 0; color: #1f2937;">Hi Blueboxx Designs &amp; Animation,</p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            A new internship application has been submitted by <strong>{{ $app->applicant_name }}</strong> for the <strong>{{ $app->internship?->title ?? $app->application_type ?? 'Internship' }}</strong> position.
        </p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            The applicant provided the following contact details: <strong>{{ $app->applicant_email }}</strong> and <strong>{{ $app->applicant_phone ?? 'N/A' }}</strong>.
        </p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            The applicant has agreed to the Terms &amp; Conditions and their digital signature has been verified. The application was submitted on <strong>{{ ($app->applied_at ? $app->applied_at->copy()->timezone('Asia/Kolkata') : ($app->created_at ? $app->created_at->copy()->timezone('Asia/Kolkata') : now('Asia/Kolkata')))->format('M d, Y \a\t h:i A') }}</strong>.
        </p>

        <p style="margin: 0 0 24px 0; color: #1f2937;">
            Please review the application from the admin panel.
        </p>

        <p style="margin: 0 0 28px 0;">
            <a href="{{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}" style="display: inline-block; background-color: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 10px 22px; font-weight: 600; font-size: 14px; border-radius: 4px;">
                Review Application
            </a>
        </p>

        <p style="margin: 28px 0 0 0; color: #374151; font-size: 14px; line-height: 1.5; border-top: 1px solid #e5e7eb; padding-top: 16px;">
            Regards,<br><br>
            <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
            Admin Notification System
        </p>
    </div>
</body>
</html>