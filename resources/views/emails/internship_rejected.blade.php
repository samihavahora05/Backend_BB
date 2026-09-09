<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status Update</title>
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
        .feedback-box {
            background-color: #f9fafb;
            border-left: 3px solid #6b7280;
            padding: 12px 16px;
            margin: 16px 0 20px 0;
            font-size: 13px;
            color: #374151;
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
        <p>Dear {{ $app->applicant_name }},</p>

        <p>Thank you for taking the time to apply for the <strong>{{ $position ?? ($app->internship?->title ?? $app->application_type ?? 'Internship') }}</strong> opportunity with <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong></p>

        <p>After careful review of all submissions for this cohort, we regret to inform you that we are unable to move forward with your application at this time.</p>

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
                <td class="value">Not Selected</td>
            </tr>
        </table>

        @if(!empty($reason))
        <div class="feedback-box">
            <strong>Reviewer Feedback:</strong><br>
            {{ $reason }}
        </div>
        @endif

        <p>Due to the competitive volume of applications, our selection decisions are difficult. We encourage you to continue developing your skills and explore future openings on our careers platform.</p>

        <p>We wish you every success in your academic and professional endeavors.</p>

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