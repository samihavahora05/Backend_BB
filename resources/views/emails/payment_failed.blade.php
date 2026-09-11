@extends('emails.layout')

@section('content')
    <div style="background: linear-gradient(135deg, #450a0a 0%, #1e1b4b 100%); border-radius: 12px 12px 0 0; padding: 28px 24px; text-align: center;">
        <div style="display: inline-block; width: 52px; height: 52px; background-color: rgba(239, 68, 68, 0.2); border: 2px solid #ef4444; border-radius: 50%; line-height: 48px; font-size: 26px; color: #ef4444; margin-bottom: 12px;">!</div>
        <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px;">Payment Interrupted</h1>
        <p style="color: #fca5a5; font-size: 13px; margin: 0; font-weight: 500;">Let's help you complete your enrollment</p>
    </div>

    <div style="padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; background: #ffffff;">
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Hi <strong>{{ $user ? trim($user->first_name . ' ' . $user->last_name) : 'there' }}</strong>,
        </p>

        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px;">
            We noticed that your recent attempt to enroll in <strong>{{ $itemTitle ?? 'your Blueboxx program' }}</strong> was not completed.
        </p>

        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            <strong style="color: #0d1635;">Zero Risk Guarantee:</strong> If any funds were deducted by your bank or UPI app, Razorpay will automatically reverse the transaction to your original payment method within 3 to 5 business days.
        </p>

        <!-- Attempt Summary -->
        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 18px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                @if($itemTitle)
                <tr>
                    <td style="padding: 5px 0; color: #9f1239; width: 140px;">Selected Item:</td>
                    <td style="padding: 5px 0; font-weight: 700; color: #881337;">{{ $itemTitle }}</td>
                </tr>
                @endif
                @if($order)
                <tr>
                    <td style="padding: 5px 0; color: #9f1239; width: 140px;">Order Reference:</td>
                    <td style="padding: 5px 0; font-family: monospace; font-weight: 700; color: #881337;">
                        {{ $order->order_number ?? ('ORD-' . $order->id) }}
                    </td>
                </tr>
                @endif
                @if($amount)
                <tr>
                    <td style="padding: 5px 0; color: #9f1239;">Amount:</td>
                    <td style="padding: 5px 0; font-weight: 800; color: #881337;">â‚¹{{ number_format((float)$amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 5px 0; color: #9f1239;">Status Reason:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #b91c1c;">
                        {{ $reason }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Quick Tips for Smooth Payment -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 800; color: #1e293b;">
                ðŸ’¡ Quick solutions to complete your booking:
            </p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.6;">
                <li>Try paying via <strong>Google Pay / PhonePe / Paytm UPI</strong> for instantaneous processing.</li>
                <li>Ensure online transaction limits are enabled in your banking app.</li>
                <li>Use an alternate debit/credit card or NetBanking.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ $retryUrl }}" 
               style="display: inline-block; background-color: #0d1635; color: #ffffff !important; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(13, 22, 53, 0.25);">
               Complete Your Enrollment &rarr;
            </a>
        </div>

        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 20px;">
            Need help with payment assistance, split payments, or have questions about the curriculum? Simply <strong>reply directly to this email</strong> and our admissions advisor will assist you within minutes.
        </p>

        <div class="signature">
            <p style="margin: 0; font-weight: 700; color: #0d1635;">Admissions & Enrollment Team</p>
            <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Blueboxx DA Pvt. Ltd. Â· Vadodara, Gujarat</p>
        </div>
    </div>
@endsection
