@extends('emails.layout')

@section('content')
    <div style="background: linear-gradient(135deg, #0d1635 0%, #1B2A6B 100%); border-radius: 12px 12px 0 0; padding: 28px 24px; text-align: center;">
        <div style="display: inline-block; width: 52px; height: 52px; background-color: rgba(201, 162, 39, 0.2); border: 2px solid #C9A227; border-radius: 50%; line-height: 48px; font-size: 26px; color: #C9A227; margin-bottom: 12px;">ðŸ“…</div>
        <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px;">
            {{ $recipientRole === 'expert' ? 'New Mentorship Session Booked' : 'Session Booking Confirmed!' }}
        </h1>
        <p style="color: #cbd5e1; font-size: 13px; margin: 0; font-weight: 500;">Blueboxx 1:1 Professional Guidance</p>
    </div>

    <div style="padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; background: #ffffff;">
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Hello <strong>{{ $recipientRole === 'expert' ? ($expert?->user?->name ?? 'Expert Mentor') : ($student?->name ?? 'Learner') }}</strong>,
        </p>

        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            @if($recipientRole === 'expert')
                A student has booked a 1:1 mentorship session with you. Here are the details of the upcoming appointment:
            @else
                Your 1:1 guidance session with industry mentor <strong>{{ $expert?->user?->name ?? 'Blueboxx DA Mentor' }}</strong> is confirmed and locked in the calendar!
            @endif
        </p>

        <!-- Session Details Box -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 140px;">Topic / Session:</td>
                    <td style="padding: 6px 0; font-weight: 700; color: #0d1635;">
                        {{ $booking->session?->title ?? '1:1 Career Guidance & Mentorship' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">
                        {{ $recipientRole === 'expert' ? 'Student Name:' : 'Mentor:' }}
                    </td>
                    <td style="padding: 6px 0; font-weight: 700; color: #1B2A6B;">
                        {{ $recipientRole === 'expert' ? ($student?->name ?? 'Student') : ($expert?->user?->name ?? 'Industry Expert') }}
                        @if($recipientRole !== 'expert' && $expert?->designation)
                            <span style="font-size: 11px; color: #64748b; font-weight: normal;">({{ $expert->designation }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Date:</td>
                    <td style="padding: 6px 0; font-weight: 700; color: #1e293b;">
                        {{ $booking->booking_date ? $booking->booking_date->format('l, d F Y') : now()->format('l, d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Time Slot:</td>
                    <td style="padding: 6px 0; font-weight: 700; color: #1e293b;">
                        {{ $booking->start_time ?? '10:00 AM' }} - {{ $booking->end_time ?? '11:00 AM' }} (IST)
                    </td>
                </tr>
                @if($booking->amount > 0)
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Fee:</td>
                    <td style="padding: 6px 0; font-weight: 800; color: #10b981;">
                        â‚¹{{ number_format($booking->amount, 2) }} (Confirmed)
                    </td>
                </tr>
                @endif
                @if($booking->student_notes)
                <tr>
                    <td style="padding: 6px 0; color: #64748b; vertical-align: top;">Student Notes:</td>
                    <td style="padding: 6px 0; color: #334155; font-style: italic;">
                        â€œ{{ $booking->student_notes }}â€
                    </td>
                </tr>
                @endif
            </table>
        </div>

        @if($booking->meeting_link)
        <!-- Meeting Link CTA -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 18px; margin: 20px 0; text-align: center;">
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #166534;">Your Live Video Meeting Link:</p>
            <a href="{{ $booking->meeting_link }}" target="_blank" style="font-size: 14px; font-weight: 800; color: #15803d; text-decoration: underline; word-break: break-all;">
                {{ $booking->meeting_link }}
            </a>
        </div>
        @endif

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ config('app.frontend_url', 'https://blueboxx.in') }}/student/dashboard" 
               style="display: inline-block; background-color: #1B2A6B; color: #ffffff !important; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(27, 42, 107, 0.25);">
               View In My Sessions &rarr;
            </a>
        </div>

        <div class="signature">
            <p style="margin: 0; font-weight: 700; color: #0d1635;">Blueboxx DA Mentorship Cell</p>
            <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Have questions before your call? Reply directly to this email for instant support.</p>
        </div>
    </div>
@endsection
