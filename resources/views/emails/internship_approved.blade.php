<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #1f2937; font-size: 15px; line-height: 1.6; margin: 0; padding: 24px;">
    <div style="max-width: 580px; margin: 0 auto; background-color: #ffffff;">
        <p style="margin: 0 0 16px 0; color: #1f2937;">Hi {{ $studentName ?? $app->applicant_name }},</p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            Your application for the <strong>{{ $position ?? ($app->internship?->title ?? $app->application_type ?? 'Internship') }}</strong> internship has been approved by BlueBoxx Designs &amp; Animation Pvt. Ltd.
        </p>

        <p style="margin: 0 0 16px 0; color: #1f2937;">
            Your official appointment letter is now available.
        </p>

        <p style="margin: 0 0 20px 0; color: #1f2937; line-height: 1.8;">
            <strong>Reference ID:</strong> {{ $referenceId ?? ($app->reference_id ?? ('BB-INT-' . str_pad($app->id, 5, '0', STR_PAD_LEFT))) }}<br>
            <strong>Approved:</strong> {{ $approvedDate ?? ($app->updated_at ? $app->updated_at->format('M d, Y') : now()->format('M d, Y')) }}
        </p>

        <p style="margin: 0 0 28px 0;">
            <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" style="display: inline-block; background-color: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 10px 22px; font-weight: 600; font-size: 14px; border-radius: 4px;">
                View Appointment Letter
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