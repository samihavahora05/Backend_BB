<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>Application Received</title>
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
        @media only screen and (max-width: 620px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
            .mobile-label {
                width: 120px !important;
            }
        }
    </style>
</head>
<body bgcolor="#ffffff" style="margin: 0; padding: 0; background-color: #ffffff; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff" style="background-color: #ffffff; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 24px 12px; background-color: #ffffff;" bgcolor="#ffffff">
                <!-- Main Email Card (Max 600px) -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; text-align: left;" bgcolor="#ffffff">
                    
                    <!-- Minimal Brand Header -->
                    <tr>
                        <td style="padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; font-weight: 700; color: #1e3a8a; letter-spacing: 0.2px;">
                            BlueBoxx Designs &amp; Animation
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding-top: 20px; font-size: 15px; line-height: 1.6; color: #1f2937;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                Hi {{ $applicantName }},
                            </p>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                Thank you for applying for the <strong>{{ $internshipTitle }}</strong> internship at BlueBoxx Designs &amp; Animation Pvt. Ltd.
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #1f2937;">
                                We have received your application, verified your Terms &amp; Conditions acceptance, and recorded your digital signature.
                            </p>

                            <!-- Section: Application Details -->
                            <div style="font-size: 15px; font-weight: 700; color: #111827; margin: 20px 0 10px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb;">
                                Application Details
                            </div>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px; font-size: 14px; line-height: 1.5; color: #1f2937;">
                                @if(!empty($app?->id))
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Application ID:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">#{{ $app->id }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Position:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">{{ $internshipTitle }}</td>
                                </tr>
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Submitted On:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">{{ $appliedDate }}</td>
                                </tr>
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Application Status:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">{{ $status }}</td>
                                </tr>
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Terms &amp; Conditions:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">Agreed ({{ $termsVersion }})</td>
                                </tr>
                                <tr>
                                    <td class="mobile-label" style="padding: 5px 0; width: 140px; color: #4b5563; vertical-align: top; font-size: 14px;">Digital Signature:</td>
                                    <td style="padding: 5px 0; color: #111827; font-weight: 600; vertical-align: top; font-size: 14px;">Verified</td>
                                </tr>
                            </table>

                            <!-- Section: Next Steps -->
                            <div style="font-size: 15px; font-weight: 700; color: #111827; margin: 20px 0 10px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb;">
                                Next Steps
                            </div>

                            <ol style="margin: 8px 0 20px 0; padding-left: 20px; font-size: 14px; line-height: 1.6; color: #374151;">
                                <li style="margin-bottom: 6px;"><strong>Application Review:</strong> Our team will evaluate your profile within 1–2 business days.</li>
                                <li style="margin-bottom: 6px;"><strong>Domain Matching:</strong> Shortlisted candidates are mapped to live project tracks.</li>
                                <li style="margin-bottom: 6px;"><strong>Formal Approval:</strong> Once approved, your official appointment letter will be issued directly on your dashboard.</li>
                            </ol>

                            <!-- Clean Medium CTA Button -->
                            <table border="0" cellpadding="0" cellspacing="0" style="margin: 22px 0 16px 0;">
                                <tr>
                                    <td align="center" bgcolor="#1e3a8a" style="border-radius: 4px; background-color: #1e3a8a;">
                                        <a href="{{ $studentPortalUrl ?? ($frontendUrl . '/login?redirect=/student/applications') }}" target="_blank" style="display: inline-block; padding: 10px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; color: #ffffff !important; text-decoration: none; border-radius: 4px; border: 1px solid #1e3a8a;">
                                            Track Application on Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Minimal Signature -->
                            <div style="border-top: 1px solid #f3f4f6; padding-top: 16px; margin-top: 24px; font-size: 14px; line-height: 1.5; color: #374151;">
                                Regards,<br><br>
                                <strong>BlueBoxx Designs &amp; Animation Pvt. Ltd.</strong><br>
                                <a href="mailto:info.blueboxx@gmail.com" style="color: #2563eb; text-decoration: none; font-size: 13px;">info.blueboxx@gmail.com</a><br>
                                <a href="{{ $frontendUrl ?? 'https://sarvakshetra.com' }}" style="color: #2563eb; text-decoration: none; font-size: 13px;">sarvakshetra.com</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
