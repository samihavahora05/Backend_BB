<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use App\Jobs\SendQueuedEmailJob;
use App\Mail\CourseEnrollmentMail;
use App\Notifications\PlatformNotification;


class PaymentController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string'
        ]);

        $gatewayService = app(\App\Services\Payments\PaymentGatewayInterface::class);
        $isValid = $gatewayService->verifyPayment($request->all());

        if (!$isValid) {
            return response()->json(['message' => 'Razorpay payment signature verification failed.'], 400);
        }

        try {

            $order = Order::where('order_number', $request->razorpay_order_id)
                ->orWhere('id', $request->razorpay_order_id)
                ->firstOrFail();
                
            $order->update(['status' => 'completed']);

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'transaction_id' => $request->razorpay_payment_id,
                'payment_gateway' => 'razorpay',
                'amount' => $order->total_amount,
                'status' => 'successful'
            ]);

            // Clear Cart
            Cart::where('user_id', $request->user()->id)->delete();

            // Enroll Student and Send Email & Notifications
            $orderItems = \App\Models\OrderItem::where('order_id', $order->id)->get();
            foreach ($orderItems as $item) {
                $courseId = $item->course_id ?? $item->purchasable_id;
                if ($courseId) {
                    $course = \App\Models\Course::with('expert')->find($courseId);
                    if ($course) {
                        \App\Models\CourseEnrollment::firstOrCreate([
                            'user_id' => $request->user()->id,
                            'course_id' => $course->id,
                        ], [
                            'enrolled_at' => now(),
                            'status' => 'active'
                        ]);

                        // Send course enrollment notification
                        try {
                            $request->user()->notify(new PlatformNotification(
                                "Course Enrolled! 🎓",
                                "You have successfully enrolled in: '{$course->title}'.",
                                'course_enrolled',
                                ['course_id' => $course->id]
                            ));

                            SendQueuedEmailJob::dispatch(
                                $request->user()->email,
                                new CourseEnrollmentMail($course->title, now()->toDateString(), route('courses.show', $course->id)),
                                'Course Enrollment Confirmation'
                            );
                        } catch (\Throwable $err) {
                            \Illuminate\Support\Facades\Log::warning("Enrollment notification warning: " . $err->getMessage());
                        }
                    }
                }
            }

            // Send general payment success notification
            $request->user()->notify(new PlatformNotification(
                "Payment Success! 💳",
                "Your payment of {$order->total_amount} for Order ID {$order->id} was successful.",
                'payment_success',
                ['order_id' => $order->id]
            ));

            return response()->json([
                'message' => 'Payment successful',
                'payment' => $payment
            ]);


        } catch (SignatureVerificationError $e) {
            $order = Order::where('order_number', $request->razorpay_order_id)->first();
            if ($order) {
                $order->update(['status' => 'failed']);
                
                Payment::create([
                    'order_id' => $order->id,
                    'user_id' => $request->user()->id,
                    'transaction_id' => $request->razorpay_payment_id,
                    'payment_gateway' => 'razorpay',
                    'amount' => $order->total_amount,
                    'status' => 'failed'
                ]);
            }

            return response()->json(['message' => 'Payment verification failed', 'error' => $e->getMessage()], 400);
        }
    }
}
