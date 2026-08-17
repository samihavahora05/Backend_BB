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

        $orders = Order::with(['items', 'payments'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                $firstPayment = $order->payments->first();
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => (float)$order->total_amount,
                    'status' => strtolower($order->status) === 'completed' ? 'Completed' : ucfirst($order->status),
                    'created_at' => $order->created_at->toIso8601String(),
                    'payment_id' => $firstPayment ? ($firstPayment->transaction_id ?? $order->order_number) : $order->order_number,
                    'payment_method' => $firstPayment ? ($firstPayment->payment_method ?? 'Razorpay') : 'Razorpay',
                    'items' => $order->items->map(function ($item) {
                        $title = '1:1 Mentorship Session';
                        if ($item->purchasable_type === \App\Models\Course::class || str_contains($item->purchasable_type, 'Course')) {
                            $course = \App\Models\Course::find($item->purchasable_id);
                            $title = $course ? $course->title : 'Course Enrollment';
                        } elseif ($item->purchasable_type === \App\Models\MentorBooking::class || str_contains($item->purchasable_type, 'MentorBooking')) {
                            $booking = \App\Models\MentorBooking::with('session', 'expert.user')->find($item->purchasable_id);
                            if ($booking) {
                                $expertName = 'Mentor';
                                $notes = $booking->student_notes ?? '';
                                if (preg_match('/with\s+([A-Za-z\s]+)/i', $notes, $matches) && !str_contains($matches[1], 'Loading')) {
                                    $expertName = trim($matches[1]);
                                } elseif ($booking->expert && $booking->expert->user && $booking->expert->user->name && !str_contains($booking->expert->user->name, 'Loading')) {
                                    $expertName = $booking->expert->user->name;
                                } elseif ($booking->expert && $booking->expert->designation) {
                                    $expertName = $booking->expert->designation;
                                }
                                $title = ($booking->session?->title ?? '1:1 Mentorship Session') . " with " . $expertName;
                            }
                        }
                        return [
                            'id' => $item->id,
                            'title' => $title,
                            'price' => (float)$item->price,
                            'quantity' => $item->quantity
                        ];
                    }),
                ];
            });

        $totalSpent = $orders->filter(fn($o) => strtolower($o['status']) === 'completed')->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'total_spent' => $totalSpent
            ]
        ]);
    }
}