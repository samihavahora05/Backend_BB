<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BlueBoxx' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 24px;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        h2 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 16px 0;
        }
        p {
            margin: 0 0 16px 0;
            color: #1f2937;
        }
        .btn {
            display: inline-block;
            background-color: #1e3a8a;
            color: #ffffff !important;
            padding: 10px 22px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            margin: 16px 0;
        }
        .details-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 16px 0 20px 0;
        }
        .signature {
            margin-top: 28px;
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
            border-top: 1px solid #f3f4f6;
            padding-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
