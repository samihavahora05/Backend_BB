<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #1B2A6B; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: bold; }
        .content { padding: 24px; font-size: 14px; line-height: 1.6; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .info-table td.label { font-weight: bold; color: #64748b; width: 35%; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 9999px; font-size: 11px; font-weight: bold; background: #e0e7ff; color: #3730a3; text-transform: uppercase; }
        .btn-box { text-align: center; margin: 24px 0 16px 0; }
        .btn { display: inline-block; background: #1B2A6B; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 14px; }
        .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Internship Application Received</h1>
        </div>
        <div class="content">
            <p>A new applicant has submitted an internship application with verified Terms & Conditions acceptance and digital signature.</p>

            <table class="info-table">
                <tr>
                    <td class="label">Application ID</td>
                    <td>#{{ $app->id }}</td>
                </tr>
                <tr>
                    <td class="label">Applicant Name</td>
                    <td><strong>{{ $app->applicant_name }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Email Address</td>
                    <td>{{ $app->applicant_email }}</td>
                </tr>
                <tr>
                    <td class="label">Phone Number</td>
                    <td>{{ $app->applicant_phone }}</td>
                </tr>
                <tr>
                    <td class="label">Position Applied</td>
                    <td><strong>{{ $app->internship?->title ?? $app->application_type ?? 'General Program' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">T&C Status</td>
                    <td><span class="badge" style="background:#dcfce7; color:#15803d;">Agreed ({{ $app->terms_version ?? 'v1.0' }})</span></td>
                </tr>
                <tr>
                    <td class="label">Digital Signature</td>
                    <td><span class="badge" style="background:#e0f2fe; color:#0369a1;">Verified</span></td>
                </tr>
                <tr>
                    <td class="label">Submitted Date</td>
                    <td>{{ $app->applied_at ? $app->applied_at->format('M d, Y h:i A') : now()->format('M d, Y') }}</td>
                </tr>
            </table>

            <div class="btn-box">
                <a href="{{ url('/admin/internships/applications') }}" class="btn">Review Application in Admin Panel</a>
            </div>
        </div>
        <div class="footer">
            Blueboxx Designs & Animation • Admin Notification System
        </div>
    </div>
</body>
</html>