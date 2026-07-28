<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 50px;
            box-sizing: border-box;
            text-align: center;
            position: relative;
            /* In production, add a background image here */
            background: #ffffff;
            border: 20px solid #1B2A6B; /* Blueboxx primary color */
        }
        .header {
            margin-top: 50px;
        }
        .header h1 {
            font-size: 50px;
            color: #1B2A6B;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .subtitle {
            font-size: 24px;
            color: #666;
            margin-top: 10px;
        }
        .student-name {
            font-size: 48px;
            font-weight: bold;
            color: #222;
            margin: 40px 0;
            border-bottom: 2px solid #ddd;
            display: inline-block;
            padding-bottom: 5px;
        }
        .course-name {
            font-size: 32px;
            color: #1B2A6B;
            font-weight: bold;
            margin: 20px 0;
        }
        .date-section {
            margin-top: 50px;
            font-size: 20px;
            color: #555;
        }
        .footer {
            position: absolute;
            bottom: 50px;
            width: 90%;
            left: 5%;
        }
        .signature {
            float: left;
            width: 30%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 50px;
        }
        .qr-code {
            float: right;
            width: 100px;
            height: 100px;
        }
        .cert-id {
            clear: both;
            margin-top: 120px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="header">
            <h1>Certificate of Completion</h1>
            <div class="subtitle">This is to certify that</div>
        </div>

        <div class="student-name">
            {{ $student_name }}
        </div>

        <div class="subtitle">has successfully completed the course</div>

        <div class="course-name">
            {{ $course_name }}
        </div>

        <div class="date-section">
            Issued on: {{ $date }}
        </div>

        <div class="footer">
            <div class="signature">
                Director, Blueboxx
            </div>
            
            <div class="qr-code">
                <img src="data:image/svg+xml;base64,{{ $qr_code }}" alt="QR Code" width="100" height="100">
            </div>

            <div class="cert-id">
                Certificate ID: {{ $cert_id }} <br>
                Verify at: {{ url('/verify-certificate/'.$cert_id) }}
            </div>
        </div>
    </div>
</body>
</html>
