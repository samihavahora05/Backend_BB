<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEnrollmentEmailJob;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SystemApiCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Verify webhook signature using the Razorpay Webhook Secret
        $credentials = SystemApiCredential::where('provider', 'razorpay')
            ->where('status', 'active')
            ->first();

        if (!$credentials) {
            Log::error('Razorpay Webhook: No active credentials found.');
            return response()->json(['error' => 'Gateway not configured'], 500);
        }

        $webhookSecret = $credentials->metadata['webhook_secret'] ?? null;

        if ($webhookSecret) {
            $signature = $request->header('X-Razorpay-Signature');
            $body = $request->getContent();
            $expectedSig = hash_hmac('sha256', $body, $webhookSecret);

            if (!hash_equals($expectedSig, $signature ?? '')) {
                Log::warning('Razorpay Webhook: Invalid signature.');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $event = $request->input('event');
        $payload = $request->input('payload.payment.entity', []);

        if ($event === 'payment.captured') {
            $razorpayOrderId = $payload['order_id'] ?? null;

            if (!$razorpayOrderId) {
                return response()->json(['error' => 'No order_id in payload'], 400);
            }

            try {
                DB::beginTransaction();

                $payment = Payment::where('transaction_id', $razorpayOrderId)->first();

                if (!$payment || $payment->status === 'success') {
                    DB::rollBack();
                    return response()->json(['status' => 'already_processed']);
                }

                $payment->update(['status' => 'success']);
                $order = $payment->order;
                $order->update(['status' => 'completed']);

                // Fulfill enrollment
                $orderItem = $order->items()->where('purchasable_type', Course::class)->first();
                if ($orderItem) {
                    $enrollment = CourseEnrollment::firstOrCreate(
                        ['course_id' => $orderItem->purchasable_id, 'user_id' => $order->user_id],
                        ['order_id' => $order->id, 'status' => 'active', 'enrolled_at' => now()]
                    );

                    if ($enrollment->wasRecentlyCreated) {
                        $course = Course::find($orderItem->purchasable_id);
                        if ($course && $order->user) {
                            SendEnrollmentEmailJob::dispatch($order->user, $course);
                        }
                    }
                }

                DB::commit();
                return response()->json(['status' => 'ok']);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Razorpay Webhook Error: ' . $e->getMessage());
                return response()->json(['error' => 'Processing failed'], 500);
            }
        }

        return response()->json(['status' => 'event_ignored']);
    }
}
