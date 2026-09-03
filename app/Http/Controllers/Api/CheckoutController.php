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
        ]);

        $user = $request->user() ?? \App\Models\User::first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User authentication required.'], 401);
        }

        $courseIds = $request->course_ids;
        $courses = Course::where(function($q) use ($courseIds) {
            $q->whereIn('id', $courseIds)
              ->orWhereIn('slug', $courseIds);
        })->get();

        if ($courses->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No valid courses found.'], 400);
        }

        // Check if already enrolled in any
        foreach ($courses as $course) {
            if (CourseEnrollment::where('course_id', $course->id)->where('user_id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'You are already enrolled in "' . $course->title . '".'], 400);
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
                    'course_id' => $course->id,
                    'purchasable_type' => Course::class,
                    'purchasable_id' => $course->id,
                    'price' => $course->price,
                    'quantity' => 1,
                ]);
            }

            // Handle free courses (₹0 amount)
            if ($totalAmount <= 0) {
                $order->update(['status' => 'completed']);
                foreach ($courses as $course) {
                    CourseEnrollment::firstOrCreate([
                        'course_id' => $course->id,
                        'user_id'   => $user->id,
                    ], [
                        'status'      => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'is_free' => true,
                    'message' => 'Enrolled successfully in free course!',
                    'data'    => ['order_id' => $order->id]
                ]);
            }

            // Call Razorpay API for paid courses
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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
            return response()->json(['success' => false, 'message' => 'Razorpay payment signature verification failed.'], 400);
        }

        try {
            DB::beginTransaction();

            $payment = Payment::where('transaction_id', $request->razorpay_order_id)->first();
            $order = null;

            if ($payment) {
                $order = $payment->order;
            } else {
                $order = Order::where('order_number', $request->razorpay_order_id)
                    ->orWhere('id', $request->razorpay_order_id)
                    ->first();

                if ($order) {
                    $payment = Payment::firstOrCreate(['order_id' => $order->id], [
                        'gateway' => 'razorpay',
                        'payment_gateway' => 'razorpay',
                        'payment_method' => 'razorpay',
                        'transaction_id' => $request->razorpay_order_id,
                        'amount' => $order->total_amount,
                        'status' => 'pending'
                    ]);
                }
            }

            if (!$order) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
            }

            if ($payment) {
                $payment->update([
                    'status' => 'success',
                    'transaction_id' => $request->razorpay_payment_id ?? $payment->transaction_id
                ]);
            }

            $order->update(['status' => 'completed']);

            // Auto Enroll the user in all courses in this order
            $hasOrderIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('course_enrollments', 'order_id');
            $orderItems = $order->items;
            foreach ($orderItems as $orderItem) {
                $courseId = $orderItem->course_id ?? $orderItem->purchasable_id ?? $orderItem->item_id;
                if ($courseId) {
                    $attributes = [
                        'status'      => 'active',
                        'enrolled_at' => now(),
                    ];
                    if ($hasOrderIdColumn) {
                        $attributes['order_id'] = $order->id;
                    }

                    $enrollment = CourseEnrollment::firstOrCreate([
                        'course_id' => $courseId,
                        'user_id'   => $order->user_id,
                    ], $attributes);

                    if ($enrollment->wasRecentlyCreated) {
                        $course = Course::find($courseId);
                        if ($course && $order->user) {
                            try {
                                SendEnrollmentEmailJob::dispatch($order->user, $course);
                            } catch (\Throwable $mailEx) {
                                Log::warning('Failed to dispatch enrollment email: ' . $mailEx->getMessage());
                            }
                        }
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
