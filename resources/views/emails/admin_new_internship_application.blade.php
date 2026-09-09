<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Internship Application</title>
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
            padding: 9px 18px;
            border-radius: 4px;
            font-size: 13px;
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
        <p>Hi Admin,</p>

        <p>A new internship application has been submitted and is ready for review.</p>

        <div class="section-title">Application Details</div>

        <table class="details-table">
            <tr>
                <td class="label">Application ID:</td>
                <td class="value">#{{ $app->id }}</td>
            </tr>
            <tr>
                <td class="label">Applicant Name:</td>
                <td class="value">{{ $app->applicant_name }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td class="value">
                    <a href="mailto:{{ $app->applicant_email }}" style="color: #1f2937; text-decoration: none;">{{ $app->applicant_email }}</a>
                </td>
            </tr>
            <tr>
                <td class="label">Phone:</td>
                <td class="value">{{ $app->applicant_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Position Applied:</td>
                <td class="value">{{ $app->internship?->title ?? $app->application_type ?? 'Internship Position' }}</td>
            </tr>
            <tr>
                <td class="label">Submitted On:</td>
                <td class="value">{{ $app->applied_at ? $app->applied_at->format('M d, Y • h:i A') : ($app->created_at ? $app->created_at->format('M d, Y • h:i A') : now()->format('M d, Y • h:i A')) }}</td>
            </tr>
            @if(!empty($app->degree) || !empty($app->graduation_year))
            <tr>
                <td class="label">Qualification:</td>
                <td class="value">{{ $app->degree ?? 'N/A' }} @if(!empty($app->graduation_year))({{ $app->graduation_year }})@endif</td>
            </tr>
            @endif
            <tr>
                <td class="label">Application Status:</td>
                <td class="value">New</td>
            </tr>
            <tr>
                <td class="label">Terms & Conditions:</td>
                <td class="value">Agreed @if(!empty($app->terms_version))({{ $app->terms_version }})@endif</td>
            </tr>
            <tr>
                <td class="label">Digital Signature:</td>
                <td class="value">Verified</td>
            </tr>
        </table>

        <p>Please review the application from the admin panel.</p>

        <div class="btn-container">
            <a href="{{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}" class="btn" target="_blank">
                Review Application
            </a>
            <div class="fallback-link">
                Or copy and paste this URL into your browser:<br>
                <a href="{{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}" target="_blank">
                    {{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}
                </a>
            </div>
        </div>

        <div class="signature">
            Regards,<br>
            <strong>{{ config('app.name', 'BlueBoxx') }}</strong><br>
            Admin Notification System
        </div>
    </div>
</body>
</html>