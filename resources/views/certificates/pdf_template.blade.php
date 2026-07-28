<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate</title>
    <style>
        body { margin: 0; padding: 0; text-align: center; font-family: sans-serif; }
        .bg { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            /* In a real scenario, output the template background image */
            /* background-image: url('{{ $certificate->template->background_image_path ?? "" }}'); */
            /* background-size: cover; */
        }
        .content { margin-top: 200px; }
        h1 { font-size: 50px; color: #1B2A6B; }
        p { font-size: 20px; color: #333; }
        .student { font-size: 40px; font-weight: bold; margin: 20px 0; }
        .course { font-size: 30px; margin: 20px 0; }
        .date { font-size: 16px; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="bg"></div>
    <div class="content">
        <h1>Certificate of Completion</h1>
        <p>This is to certify that</p>
        <div class="student">{{ $certificate->user->name ?? 'Student Name' }}</div>
        <p>has successfully completed the course</p>
        <div class="course">{{ $certificate->course->title ?? 'Course Name' }}</div>
        <div class="date">Issued on: {{ $certificate->issued_at ? $certificate->issued_at->format('F j, Y') : '' }}</div>
    </div>
</body>
</html>
