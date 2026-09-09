<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Received</title>
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
        ol {
            margin: 8px 0 20px 0;
            padding-left: 20px;
            color: #374151;
        }
        li {
            margin-bottom: 6px;
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
        <p>Hi {{ $applicantName }},</p>

        <p>Thank you for submitting your application for the <strong>{{ $internshipTitle }}</strong> internship at <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong></p>

        <p>We have successfully received your application, verified your Terms &amp; Conditions acceptance, and recorded your digital signature.</p>

        <div class="section-title">Application Details</div>

        <table class="details-table">
            @if(!empty($app?->id))
            <tr>
                <td class="label">Application ID:</td>
                <td class="value">#{{ $app->id }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Position:</td>
                <td class="value">{{ $internshipTitle }}</td>
            </tr>
            <tr>
                <td class="label">Submitted On:</td>
                <td class="value">{{ $appliedDate }}</td>
            </tr>
            <tr>
                <td class="label">Application Status:</td>
                <td class="value">{{ $status }}</td>
            </tr>
            <tr>
                <td class="label">Terms &amp; Conditions:</td>
                <td class="value">Agreed ({{ $termsVersion }})</td>
            </tr>
            <tr>
                <td class="label">Digital Signature:</td>
                <td class="value">Verified &amp; Recorded</td>
            </tr>
        </table>

        <div class="section-title">Next Steps</div>
        <ol>
            <li><strong>Application Review:</strong> Our talent review team will evaluate your profile (1–2 business days).</li>
            <li><strong>Domain Matching:</strong> Shortlisted candidates are mapped to live project tracks and mentor sessions.</li>
            <li><strong>Formal Approval:</strong> Once approved, your official appointment letter will be issued directly on your dashboard.</li>
        </ol>

        <p>You can track your application status anytime on your student dashboard:</p>

        <div class="btn-container">
            <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" class="btn" target="_blank">
                Track Application on Dashboard
            </a>
            <div class="fallback-link">
                Or copy and paste this URL into your browser:<br>
                <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" target="_blank">
                    {{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}
                </a>
            </div>
        </div>

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
