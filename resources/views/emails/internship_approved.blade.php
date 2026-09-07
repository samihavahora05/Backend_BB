<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #059669; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: bold; }
        .content { padding: 24px; font-size: 14px; line-height: 1.6; }
        .card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin: 16px 0; }
        .btn-box { text-align: center; margin: 24px 0 16px 0; }
        .btn { display: inline-block; background: #059669; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 14px; }
        .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Application Approved!</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $app->applicant_name }}</strong>,</p>

            <p>Congratulations! We are pleased to inform you that your application for the <strong>{{ $app->internship?->title ?? $app->application_type ?? 'Internship Program' }}</strong> has been <strong>Approved</strong>.</p>

            <div class="card">
                <p style="margin: 0; font-weight: bold; color: #166534;">Your Appointment Letter has been generated and is ready for download.</p>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #15803d;">You can login to your Blueboxx student dashboard to download your official letter anytime.</p>
            </div>

            <div class="btn-box">
                <a href="{{ url('/student/applications') }}" class="btn">View & Download Appointment Letter</a>
            </div>

            <p>Our onboarding team will contact you shortly with next steps and orientation details.</p>
        </div>
        <div class="footer">
            Blueboxx Designs & Animation • Career Excellence Platform
        </div>
    </div>
</body>
</html>