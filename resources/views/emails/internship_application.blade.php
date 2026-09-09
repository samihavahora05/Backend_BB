<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Received</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #1f2937; font-size: 15px; line-height: 1.6; margin: 0; padding: 24px;">
    <div style="max-width: 580px; margin: 0 auto; background-color: #ffffff;">
        <p style="margin: 0 0 16px 0; color: #1f2937;">Hi {{ $applicantName }},</p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            Thank you for applying for the <strong>{{ $internshipTitle }}</strong> internship at BlueBoxx Designs &amp; Animation Pvt. Ltd.
        </p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            We have received your application, verified your Terms &amp; Conditions acceptance, and recorded your digital signature on <strong>{{ $appliedDate }}</strong>.
        </p>

        <p style="margin: 0 0 24px 0; color: #1f2937;">
            Our team will review your application within 1–2 business days. You can track your application status anytime from your student dashboard.
        </p>

        <p style="margin: 0 0 28px 0;">
            <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" style="display: inline-block; background-color: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 10px 22px; font-weight: 600; font-size: 14px; border-radius: 4px;">
                Track Application Status
            </a>
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
