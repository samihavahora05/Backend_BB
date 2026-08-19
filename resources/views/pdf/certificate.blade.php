<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $cert_title ?? 'Certificate of Completion' }}</title>
    <style>
        @page {
            margin: 0px;
            size: A4 landscape;
        }
        html, body {
            margin: 0px;
            padding: 0px;
            width: 100%;
            height: 100%;
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #ffffff;
        }
        .cert-stage {
            position: relative;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            box-sizing: border-box;
        }
        .cert-bg-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .cert-content-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
        }
        .cert-element {
            position: absolute;
            transform: translate(-50%, -50%);
            box-sizing: border-box;
            white-space: nowrap;
        }
        
        /* Fallback Classic Layout when no custom elements defined */
        .classic-layout {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            text-align: center;
        }
        .classic-header {
            padding-top: 130px;
        }
        .classic-title {
            font-size: 38px;
            font-weight: 800;
            color: #0A192F;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
        }
        .classic-subtitle {
            font-size: 18px;
            color: #475569;
            margin-top: 25px;
        }
        .classic-name {
            font-size: 48px;
            font-weight: 800;
            color: #0F172A;
            margin: 30px 0 20px 0;
        }
        .classic-course {
            font-size: 20px;
            color: #334155;
            margin-top: 10px;
        }
        .classic-footer {
            position: absolute;
            bottom: 60px;
            width: 86%;
            left: 7%;
            font-size: 15px;
            color: #64748b;
        }
        .classic-footer-left {
            float: left;
            text-align: left;
        }
        .classic-footer-right {
            float: right;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="cert-stage">
        @if(!empty($template_bg))
            <img src="{{ $template_bg }}" class="cert-bg-img" alt="Certificate Background" />
        @endif

        @if(!empty($elements) && is_array($elements) && count($elements) > 0)
            <div class="cert-content-layer">
                @foreach($elements as $el)
                    @if(isset($el['enabled']) && !$el['enabled'])
                        @continue
                    @endif

                    @php
                        $rawContent = $el['content'] ?? '';
                        $content = str_replace(
                            ['{student_name}', '{course_title}', '{course_name}', '{issue_date}', '{date}', '{certificate_id}', '{cert_id}', '{verification_id}'],
                            [$student_name, $course_name, $course_name, $date, $date, $cert_id, $cert_id, $cert_id],
                            $rawContent
                        );

                        $transform = strtolower($el['textTransform'] ?? 'none');
                        if ($transform === 'uppercase') {
                            $content = mb_strtoupper($content);
                        } elseif ($transform === 'lowercase') {
                            $content = mb_strtolower($content);
                        } elseif ($transform === 'capitalize') {
                            $content = ucwords($content);
                        }

                        $posX = floatval($el['positionX'] ?? 50);
                        $posY = floatval($el['positionY'] ?? 50);
                        $width = floatval($el['width'] ?? 80);

                        $fontSize = intval($el['fontSize'] ?? 24);
                        $fontWeight = $el['fontWeight'] ?? 'normal';
                        $fontStyle = $el['fontStyle'] ?? 'normal';
                        $fontColor = $el['fontColor'] ?? '#0f172a';
                        $align = $el['textAlignment'] ?? 'center';
                        $letterSpacing = intval($el['letterSpacing'] ?? 0);
                    @endphp

                    <div class="cert-element" style="left: {{ $posX }}%; top: {{ $posY }}%; width: {{ $width }}%; text-align: {{ $align }}; font-size: {{ $fontSize }}px; font-weight: {{ $fontWeight }}; font-style: {{ $fontStyle }}; color: {{ $fontColor }}; {{ $letterSpacing > 0 ? 'letter-spacing: '.$letterSpacing.'px;' : '' }}">
                        {{ $content }}
                    </div>
                @endforeach
            </div>
        @else
            <!-- Standard Overlay layout if template has no custom elements defined -->
            <div class="classic-layout">
                <div class="classic-header">
                    @if(!isset($show_title) || $show_title)
                        <h1 class="classic-title">{{ $cert_title ?? 'Certificate of Completion' }}</h1>
                    @endif
                    <div class="classic-subtitle">This certificate is proudly presented to</div>
                </div>

                <div class="classic-name">
                    {{ $student_name }}
                </div>

                <div class="classic-course">
                    for successfully completing <strong>{{ $course_name }}</strong>
                </div>

                <div class="classic-footer">
                    <div class="classic-footer-left">
                        Issued: {{ $date }}
                    </div>
                    <div class="classic-footer-right">
                        Verification ID: {{ $cert_id }}
                        @if(!empty($qr_code))
                            <span style="vertical-align: middle; margin-left: 10px;">
                                <img src="data:image/svg+xml;base64,{{ $qr_code }}" width="50" height="50" alt="QR" />
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</body>
</html>