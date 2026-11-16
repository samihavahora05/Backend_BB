<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Technical Interview Invitation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 20px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .header { background: #1B2A6B; padding: 32px 24px; text-align: center; color: #ffffff; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; background: #ecfdf5; color: #047857; font-weight: bold; font-size: 12px; padding: 6px 14px; border-radius: 9999px; border: 1px solid #a7f3d0; margin-bottom: 12px; }
        .btn { display: inline-block; background: #1B2A6B; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: bold; font-size: 14px; margin: 20px 0; }
        .info-box { background: #f1f5f9; border-radius: 12px; padding: 16px; margin: 20px 0; font-size: 14px; }
        .footer { text-align: center; font-size: 12px; color: #64748b; padding: 24px; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">BlueBoxx Internship Portal</h1>
            <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Technical Interview Invitation</p>
        </div>
        <div class="content">
            <div class="badge">Assessment Passed (Score: {{ $attempt->score }}/50)</div>
            <h2 style="margin-top: 0; color: #0f172a;">Congratulations, {{ $user->name }}!</h2>
            <p style="font-size: 15px; line-height: 1.6; color: #334155;">
                We are pleased to inform you that you have successfully cleared the <strong>Common IT & Digital Skills Assessment</strong> with a qualified score of <strong>{{ $attempt->score }}/50</strong>.
            </p>
            <p style="font-size: 15px; line-height: 1.6; color: #334155;">
                As the next step of your selection process, your 1-on-1 Technical Interview has been scheduled.
            </p>

            <div class="info-box">
                <p style="margin: 0 0 8px;"><strong>Date & Time:</strong> {{ $scheduledTime->format('l, d F Y at h:i A') }}</p>
                <p style="margin: 0 0 8px;"><strong>Mode:</strong> Online Video Call (Google Meet)</p>
                <p style="margin: 0;"><strong>Link:</strong> <a href="{{ $meetLink }}" style="color: #1B2A6B; font-weight: bold;">{{ $meetLink }}</a></p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $meetLink }}" class="btn" target="_blank">Join Google Meet Interview</a>
            </div>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                Please ensure you have a stable internet connection and quiet surroundings. Be prepared with your project details and resume.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} BlueBoxx Technologies Pvt. Ltd. All rights reserved.<br>
            Vadodara, Gujarat, India.
        </div>
    </div>
</body>
</html>
