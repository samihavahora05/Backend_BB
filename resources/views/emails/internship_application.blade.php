<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Confirmation - BlueBoxx DA</title>
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
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.4);
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
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            margin: 20px 0 24px 0;
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
        .badge-pill {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            border: 1px solid #93c5fd;
        }
        .steps-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .steps-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px 0;
        }
        .steps-list {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            color: #334155;
            line-height: 1.6;
        }
        .steps-list li {
            margin-bottom: 6px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 30px 0 14px 0;
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
            <div class="brand-subtitle">Career Excellence & Internship Program</div>
            <div class="status-pill">&check; Application Successfully Submitted</div>
        </div>

        <div class="content">
            <h2 class="greeting">Dear {{ $applicantName }},</h2>
            <p class="message-p">
                Thank you for applying for the <strong>{{ $internshipTitle }}</strong> program at <strong>BlueBoxx DA</strong>. We have received your application, verified your Terms &amp; Conditions acceptance, and recorded your digital signature.
            </p>

            <div class="info-card">
                @if(!empty($app?->id))
                <div class="info-row">
                    <div class="info-label">Application ID</div>
                    <div class="info-value">#{{ $app->id }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Position</div>
                    <div class="info-value" style="color: #1e3a8a;">{{ $internshipTitle }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Organization</div>
                    <div class="info-value">{{ $companyName }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submission Date</div>
                    <div class="info-value">{{ $appliedDate }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">T&amp;C Status</div>
                    <div class="info-value">
                        <span style="display:inline-block; background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:2px 8px; border-radius:9999px; border:1px solid #86efac;">
                            Agreed &amp; Verified ({{ $termsVersion }})
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Current Status</div>
                    <div class="info-value">
                        <span class="badge-pill">{{ $status }}</span>
                    </div>
                </div>
            </div>

            <div class="steps-box">
                <div class="steps-title">What Happens Next?</div>
                <ol class="steps-list">
                    <li><strong>Application Screening:</strong> Our admissions and talent review team evaluates your profile (1–2 business days).</li>
                    <li><strong>Task &amp; Skill Alignment:</strong> Qualified candidates are matched with live project tasks and domain mentors.</li>
                    <li><strong>Formal Approval &amp; Appointment Letter:</strong> Once approved, your official letter of appointment will be generated in your student portal.</li>
                </ol>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" class="btn-primary" target="_blank">
                    Track Application on Dashboard &rarr;
                </a>
                <div class="btn-hint">Login with your registered email to monitor status, track stages, and download your letter.</div>
            </div>
        </div>

        <div class="footer">
            <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
            Empowering Careers Through Practical Industry Experience<br>
            Need assistance? Reach us at <a href="mailto:info.blueboxx@gmail.com" style="color: #2563eb; text-decoration: none;">info.blueboxx@gmail.com</a>
        </div>
    </div>
</body>
</html>
