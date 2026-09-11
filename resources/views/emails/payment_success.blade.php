@extends('emails.layout')

@section('content')
    <div style="background: linear-gradient(135deg, #0d1635 0%, #1B2A6B 100%); border-radius: 12px 12px 0 0; padding: 28px 24px; text-align: center;">
        <div style="display: inline-block; width: 52px; height: 52px; background-color: rgba(16, 185, 129, 0.2); border: 2px solid #10b981; border-radius: 50%; line-height: 48px; font-size: 26px; color: #10b981; margin-bottom: 12px;">âœ“</div>
        <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px;">Payment Confirmed</h1>
        <p style="color: #cbd5e1; font-size: 13px; margin: 0; font-weight: 500;">Official Payment Receipt & Tax Invoice</p>
    </div>

    <div style="padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; background: #ffffff;">
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Hello <strong>{{ $user ? trim($user->first_name . ' ' . $user->last_name) : 'Valued Customer' }}</strong>,
        </p>

        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            Thank you for your transaction with <strong>Blueboxx DA Pvt. Ltd.</strong> Your payment has been successfully authorized and verified by Razorpay. Your access is now immediately active.
        </p>

        <!-- Order Summary Box -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                @if($itemTitle || ($order->items && $order->items->count()))
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 140px;">Program / Item:</td>
                    <td style="padding: 6px 0; font-weight: 700; color: #0d1635;">
                        {{ $itemTitle ?? ($order->items->first()->course->title ?? 'Industry Course / Mentorship Program') }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 140px;">Order ID:</td>
                    <td style="padding: 6px 0; font-family: monospace; font-weight: 700; color: #1e293b;">
                        {{ $order->order_number ?? ('ORD-' . $order->id) }}
                    </td>
                </tr>
                @if($payment && $payment->transaction_id)
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Transaction ID:</td>
                    <td style="padding: 6px 0; font-family: monospace; font-weight: 600; color: #2563eb;">
                        {{ $payment->transaction_id }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Date & Time:</td>
                    <td style="padding: 6px 0; font-weight: 600; color: #1e293b;">
                        {{ now()->format('d F Y, h:i A') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Amount Paid:</td>
                    <td style="padding: 6px 0; font-size: 16px; font-weight: 800; color: #10b981;">
                        â‚¹{{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Payment Status:</td>
                    <td style="padding: 6px 0; font-weight: 700; color: #10b981;">
                        Paid & Verified (256-Bit SSL Secured) âœ“
                    </td>
                </tr>
            </table>
        </div>

        <!-- Next Steps -->
        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;">
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px;">
                ðŸš€ What to do next:
            </p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #1e3a8a; line-height: 1.6;">
                <li>Access your course materials or booked sessions directly in your <strong>Student Dashboard</strong>.</li>
                <li>Your certificate tracking and live sessions are automatically linked to this account.</li>
                <li>Keep this email as your official digital invoice and proof of purchase.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ config('app.frontend_url', 'https://blueboxx.in') }}/student/dashboard" 
               style="display: inline-block; background-color: #1B2A6B; color: #ffffff !important; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(27, 42, 107, 0.25);">
               Go to Student Dashboard &rarr;
            </a>
        </div>

        <div class="signature">
            <p style="margin: 0; font-weight: 700; color: #0d1635;">Blueboxx DA Support & Billing Team</p>
            <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Need assistance? Reply directly to this email or visit <a href="{{ config('app.frontend_url', 'https://blueboxx.in') }}/contact" style="color: #1B2A6B; font-weight: 600;">our Help Center</a>.</p>
        </div>
    </div>
@endsection
