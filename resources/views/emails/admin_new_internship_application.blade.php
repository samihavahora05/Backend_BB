<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>New Internship Application</title>
    <style type="text/css">
        :root {
            color-scheme: light;
            supported-color-schemes: light;
        }
        body, table, td, p, a, li {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: #ffffff !important;
            color: #1f2937 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
        }
    </style>
</head>
<body bgcolor="#ffffff" style="margin: 0; padding: 0; background-color: #ffffff; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff" style="background-color: #ffffff; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 24px 12px; background-color: #ffffff;" bgcolor="#ffffff">
                <!-- Main Email Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; text-align: left;" bgcolor="#ffffff">
                    <tr>
                        <td style="font-size: 15px; line-height: 1.6; color: #1f2937;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                Hi Blueboxx Designs &amp; Animation,
                            </p>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                A new internship application has been submitted by <strong>{{ $app->applicant_name }}</strong> for the <strong>{{ $app->internship?->title ?? $app->application_type ?? 'Internship' }}</strong> position.
                            </p>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                The applicant provided the following contact details: <a href="mailto:{{ $app->applicant_email }}" style="color: #2563eb; text-decoration: none;">{{ $app->applicant_email }}</a> and <strong>{{ $app->applicant_phone ?? 'N/A' }}</strong>.
                            </p>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                The applicant has agreed to the Terms &amp; Conditions and their digital signature has been verified. The application was submitted on <strong>{{ $app->applied_at ? $app->applied_at->format('d M Y, h:i A') : ($app->created_at ? $app->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A')) }}</strong>.
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                Please review the application from the admin panel.
                            </p>

                            <!-- Review Application Button -->
                            <table border="0" cellpadding="0" cellspacing="0" style="margin: 20px 0 24px 0;">
                                <tr>
                                    <td align="center" bgcolor="#1e3a8a" style="border-radius: 4px; background-color: #1e3a8a;">
                                        <a href="{{ $adminReviewUrl ?? ($frontendUrl . '/login?redirect=/admin/internships/applications') }}" target="_blank" style="display: inline-block; padding: 10px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; color: #ffffff !important; text-decoration: none; border-radius: 4px; border: 1px solid #1e3a8a;">
                                            Review Application
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Signature -->
                            <div style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 24px; font-size: 14px; line-height: 1.5; color: #374151;">
                                Regards,<br><br>
                                <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
                                Admin Notification System
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>