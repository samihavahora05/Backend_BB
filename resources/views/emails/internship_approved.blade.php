<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved - BlueBoxx DA</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b1329;
            color: #334155;
            margin: 0;
            padding: 24px 12px;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .brand-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1e293b 100%);
            padding: 30px 24px;
            text-align: center;
            border-bottom: 3px solid #10b981;
        }
        .brand-logo-text {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .brand-logo-text span {
            color: #f59e0b;
        }
        .brand-subtitle {
            color: #94a3b8;
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 4px;
            font-weight: 600;
        }
        .status-pill {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.5);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 9999px;
            margin-top: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
        }
        .message-p {
            font-size: 14px;
            color: #475569;
            line-height: 1.65;
            margin: 0 0 20px 0;
        }
        .card-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 20px 0 24px 0;
        }
        .btn-wrapper {
            text-align: center;
            margin: 30px 0 14px 0;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.3px;
            box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3);
        }
        .btn-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 8px;
            text-align: center;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand-bar">
            <h1 class="brand-logo-text">BlueBoxx <span>DA</span></h1>
            <div class="brand-subtitle">Career Excellence &amp; Internship Program</div>
            <div class="status-pill">&check; Official Application Approved</div>
        </div>

        <div class="content">
            <h2 class="greeting">Dear {{ $app->applicant_name }},</h2>
            <p class="message-p">
                Congratulations! We are delighted to inform you that your application for the <strong>{{ $app->internship?->title ?? $app->application_type ?? 'Internship Program' }}</strong> has been formally <strong>Approved</strong> by BlueBoxx DA management.
            </p>

            <div class="card-success">
                <p style="margin: 0; font-weight: 700; font-size: 15px; color: #166534;">
                    Your Official Appointment Letter is ready for download!
                </p>
                <p style="margin: 6px 0 0 0; font-size: 13px; color: #15803d; line-height: 1.5;">
                    Your digital appointment letter containing your reference ID, stipend policy, milestone criteria, and induction schedule has been generated and sealed.
                </p>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" class="btn-primary" target="_blank">
                    Download Appointment Letter &rarr;
                </a>
                <div class="btn-hint">Access your dashboard anytime to view credentials and letter.</div>
            </div>

            <p style="font-size: 13px; color: #64748b; margin-top: 24px; line-height: 1.5;">
                Our onboarding team will contact you shortly with mentor allocation and live project credentials.
            </p>
        </div>

        <div class="footer">
            <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
            Empowering Careers Through Practical Industry Experience<br>
            Need assistance? Reach us at <a href="mailto:info.blueboxx@gmail.com" style="color: #2563eb; text-decoration: none;">info.blueboxx@gmail.com</a>
        </div>
    </div>
</body>
</html>