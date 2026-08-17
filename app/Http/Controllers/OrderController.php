<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('orderItems.course')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();
            
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $courseIds = $request->input('course_ids', []);

        if (empty($courseIds)) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $courses = \App\Models\Course::whereIn('id', $courseIds)->get();
        if ($courses->isEmpty()) {
            return response()->json(['message' => 'Invalid courses'], 400);
        }

        // Calculate total securely from DB prices
        $totalAmount = $courses->sum(function($course) {
            return $course->discount_price > 0 ? $course->discount_price : $course->price;
        });
        $receiptId = 'rcpt_' . Str::random(10);

        $amountInPaise = (int)($totalAmount * 100);
        $gatewayService = app(\App\Services\Payments\PaymentGatewayInterface::class);
        
        try {
            $gatewayOrder = $gatewayService->createOrder($receiptId, $amountInPaise, 'INR');
            $orderId = $gatewayOrder['order_id'];
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create Razorpay Order: ' . $e->getMessage()], 400);
        }

        try {
            // Use DB Transaction to ensure both Order and OrderItems are created safely
            DB::transaction(function () use ($user, $orderId, $totalAmount, $courses, &$order) {
                // Create Local Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => $orderId,
                    'total_amount' => $totalAmount,
                    'status' => 'pending'
                ]);

                // Create Order Items
                foreach ($courses as $course) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'course_id' => $course->id,
                        'price' => $course->discount_price > 0 ? $course->discount_price : $course->price
                    ]);
                }
            });

            return response()->json([
                'order' => $order,
                'razorpay_order_id' => $orderId,
                'amount' => $totalAmount,
                'key' => $razorpayKey
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $order = Order::with('orderItems.course')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
            
        return response()->json($order);
    }
}
