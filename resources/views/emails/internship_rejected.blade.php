<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #475569; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: bold; }
        .content { padding: 24px; font-size: 14px; line-height: 1.6; }
        .reason-box { background: #f8fafc; border-left: 4px solid #94a3b8; padding: 12px 16px; margin: 16px 0; font-style: italic; }
        .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Application Status Update</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $app->applicant_name }}</strong>,</p>

            <p>Thank you for your interest in the <strong>{{ $app->internship?->title ?? $app->application_type ?? 'Internship Program' }}</strong> with Blueboxx.</p>

            <p>After careful review of all submissions, we regret to inform you that we are unable to move forward with your application for this cohort.</p>

            @if($reason)
                <div class="reason-box">
                    <strong>Reviewer Feedback:</strong> {{ $reason }}
                </div>
            @endif

            <p>We encourage you to explore other open opportunities and continue enhancing your skills through our learning resources.</p>
        </div>
        <div class="footer">
            Blueboxx Designs & Animation • Career Excellence Platform
        </div>
    </div>
</body>
</html>