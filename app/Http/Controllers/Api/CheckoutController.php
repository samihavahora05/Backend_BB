<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\CourseEnrollment;
use App\Jobs\SendEnrollmentEmailJob;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckoutController extends Controller
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'course_ids' => 'required|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $user = $request->user();
        $courses = Course::whereIn('id', $request->course_ids)->get();

        if ($courses->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No valid courses found.'], 400);
        }

        // Check if already enrolled in any
        foreach ($courses as $course) {
            if (CourseEnrollment::where('course_id', $course->id)->where('user_id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'You are already enrolled in ' . $course->title], 400);
            }
        }

        try {
            DB::beginTransaction();

            $totalAmount = $courses->sum('price');

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($courses as $course) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'purchasable_type' => Course::class,
                    'purchasable_id' => $course->id,
                    'price' => $course->price,
                    'quantity' => 1,
                ]);
            }

            // Call Razorpay API
            $amountInPaise = (int)($totalAmount * 100);
            $gatewayOrder = $this->paymentGateway->createOrder($order->order_number, $amountInPaise, 'INR');

            // Store pending payment
            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'razorpay',
                'transaction_id' => $gatewayOrder['order_id'],
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'key' => config('services.razorpay.key'),
                'amount' => $gatewayOrder['amount'],
                'razorpay_order_id' => $gatewayOrder['order_id'],
                'data' => [
                    'order_id' => $order->id,
                    'gateway_order_id' => $gatewayOrder['order_id'],
                    'amount' => $gatewayOrder['amount'],
                    'currency' => $gatewayOrder['currency'],
                    'user' => [
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone ?? '',
                    ]
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout Create Order Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to initiate payment. Please try again later.'], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $isValid = $this->paymentGateway->verifyPayment($request->all());

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Payment signature verification failed.'], 400);
        }

        try {
            DB::beginTransaction();

            $payment = Payment::where('transaction_id', $request->razorpay_order_id)->firstOrFail();
            $order = $payment->order;

            if ($payment->status === 'success') {
                DB::rollBack();
                return response()->json(['success' => true, 'message' => 'Payment already verified.']);
            }

            $payment->update([
                'status' => 'success',
            ]);

            $order->update(['status' => 'completed']);

            // Auto Enroll the user in all courses
            $orderItems = $order->items()->where('purchasable_type', Course::class)->get();
            foreach ($orderItems as $orderItem) {
                $enrollment = CourseEnrollment::firstOrCreate([
                    'course_id' => $orderItem->purchasable_id,
                    'user_id' => $order->user_id,
                ], [
                    'order_id' => $order->id,
                    'status' => 'active',
                ]);

                // Dispatch enrollment confirmation email
                if ($enrollment->wasRecentlyCreated) {
                    $course = Course::find($orderItem->purchasable_id);
                    if ($course) {
                        SendEnrollmentEmailJob::dispatch($order->user, $course);
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Payment verified and course enrolled successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout Verify Payment Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while processing the payment.'], 500);
        }
    }
}
