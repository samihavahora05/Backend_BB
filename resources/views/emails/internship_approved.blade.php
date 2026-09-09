<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #ffffff;
            color: #1f2937;
            margin: 0;
            padding: 24px;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        p {
            margin: 0 0 16px 0;
            color: #1f2937;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin: 24px 0 12px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table td {
            padding: 6px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .details-table .label {
            width: 180px;
            color: #4b5563;
            font-weight: 600;
        }
        .details-table .value {
            color: #111827;
            font-weight: 500;
        }
        .btn-container {
            margin: 24px 0;
        }
        .btn {
            display: inline-block;
            background-color: #1e3a8a;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        .fallback-link {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
            word-break: break-all;
        }
        .fallback-link a {
            color: #2563eb;
            text-decoration: underline;
        }
        .signature {
            margin-top: 28px;
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Hi {{ $studentName ?? $app->applicant_name }},</p>

        <p><strong>Congratulations!</strong></p>

        <p>Your application for the <strong>{{ $position ?? ($app->internship?->title ?? $app->application_type ?? 'Internship') }}</strong> internship has been approved by <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong></p>

        <p>Your appointment details and official appointment letter are now available in your dashboard.</p>

        <div class="section-title">Application Details</div>

        <table class="details-table">
            <tr>
                <td class="label">Application ID:</td>
                <td class="value">#{{ $app->id }}</td>
            </tr>
            <tr>
                <td class="label">Position:</td>
                <td class="value">{{ $position ?? ($app->internship?->title ?? $app->application_type ?? 'Internship Position') }}</td>
            </tr>
            <tr>
                <td class="label">Application Status:</td>
                <td class="value">Approved</td>
            </tr>
            <tr>
                <td class="label">Reference ID:</td>
                <td class="value">{{ $referenceId ?? ($app->reference_id ?? ('BB-INT-' . str_pad($app->id, 5, '0', STR_PAD_LEFT))) }}</td>
            </tr>
            <tr>
                <td class="label">Approved On:</td>
                <td class="value">{{ $approvedDate ?? ($app->updated_at ? $app->updated_at->format('M d, Y') : now()->format('M d, Y')) }}</td>
            </tr>
        </table>

        <p>Your official appointment letter is ready.</p>

        <p>You can access and download your appointment letter from your dashboard using the button below:</p>

        <div class="btn-container">
            <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" class="btn" target="_blank">
                View / Download Appointment Letter
            </a>
            <div class="fallback-link">
                Or copy and paste this URL into your browser:<br>
                <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" target="_blank">
                    {{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}
                </a>
            </div>
        </div>

        <p>Please keep your appointment letter and reference ID for future communication.</p>

        <p>Our onboarding team will contact you regarding the next steps, mentor allocation, project details, and induction schedule.</p>

        <div class="signature">
            Regards,<br><br>
            <strong>Internship &amp; Talent Operations Team</strong><br>
            BlueBoxx Designs &amp; Animation Pvt. Ltd.<br>
            <a href="{{ $frontendUrl ?? 'https://sarvakshetra.com' }}" style="color: #2563eb; text-decoration: none;">sarvakshetra.com</a><br>
            <a href="mailto:info.blueboxx@gmail.com" style="color: #2563eb; text-decoration: none;">info.blueboxx@gmail.com</a>
        </div>
    </div>
</body>
</html>