<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class StudentOrderController extends Controller
{
    /**
     * Get the student's payment and order history.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with(['items.course', 'payments'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->course ? $item->course->title : 'Item',
                            'price' => $item->price,
                            'quantity' => $item->quantity
                        ];
                    }),
                    'payment_method' => $order->payments->first() ? $order->payments->first()->payment_method : 'N/A',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'total_spent' => $orders->where('status', 'completed')->sum('total_amount')
            ]
        ]);
    }
}
