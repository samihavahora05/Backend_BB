<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Internship Application Received</title>
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
            border-bottom: 3px solid #d97706;
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
            background: rgba(245, 158, 11, 0.15);
            color: #fef08a;
            border: 1px solid rgba(245, 158, 11, 0.4);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-top: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px 28px;
        }
        .hero-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .hero-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin: 0 0 24px 0;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            margin-bottom: 24px;
        }
        .info-row {
            display: table;
            width: 100%;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            display: table-cell;
            width: 38%;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }
        .info-value {
            display: table-cell;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            vertical-align: middle;
        }
        .badge-verified {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            border: 1px solid #86efac;
        }
        .badge-signature {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            border: 1px solid #7dd3fc;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0 16px 0;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.3px;
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
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
            <div class="brand-subtitle">Automated Recruitment & ATS Portal</div>
            <div class="status-pill">New Candidate Application #{{ $app->id }}</div>
        </div>

        <div class="content">
            <h2 class="hero-title">New Internship Application Received</h2>
            <p class="hero-desc">
                A candidate has submitted a verified internship application with digital signature and formal acceptance of the BlueBoxx Internship Terms & Conditions.
            </p>

            <div class="info-card">
                <div class="info-row">
                    <div class="info-label">Applicant Name</div>
                    <div class="info-value">{{ $app->applicant_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Position Applied</div>
                    <div class="info-value" style="color: #1e3a8a;">{{ $app->internship?->title ?? $app->application_type ?? 'Internship Program' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">
                        <a href="mailto:{{ $app->applicant_email }}" style="color: #2563eb; text-decoration: none;">{{ $app->applicant_email }}</a>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">
                        <a href="tel:{{ $app->applicant_phone }}" style="color: #2563eb; text-decoration: none;">{{ $app->applicant_phone }}</a>
                    </div>
                </div>
                @if(!empty($app->degree) || !empty($app->graduation_year))
                <div class="info-row">
                    <div class="info-label">Qualification</div>
                    <div class="info-value">{{ $app->degree ?? 'N/A' }} @if(!empty($app->graduation_year))({{ $app->graduation_year }})@endif</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">T&C Status</div>
                    <div class="info-value">
                        <span class="badge-verified">Agreed & Verified ({{ $app->terms_version ?? 'v1.0' }})</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Digital Signature</div>
                    <div class="info-value">
                        <span class="badge-signature">Recorded & Timestamped</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submission Date</div>
                    <div class="info-value">{{ $app->applied_at ? $app->applied_at->format('M d, Y • h:i A') : now()->format('M d, Y • h:i A') }}</div>
                </div>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}" class="btn-primary" target="_blank">
                    Review Application in Admin Panel &rarr;
                </a>
                <div class="btn-hint">Direct link: clicking prompts admin login if needed, then takes you directly to the review list.</div>
            </div>
        </div>

        <div class="footer">
            <strong>BlueBoxx Designs & Animation Pvt. Ltd.</strong><br>
            Enterprise ATS & Career Operations Management<br>
            <span style="color: #94a3b8; font-size: 11px;">This is an automated notification from the BlueBoxx platform.</span>
        </div>
    </div>
</body>
</html>