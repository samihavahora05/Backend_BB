<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MentorBooking;
use Illuminate\Http\Request;

class AdminMentorBookingController extends Controller
{
    /**
     * Get a list of all mentor bookings with relationships
     */
    public function index(Request $request)
    {
        $query = MentorBooking::with(['student:id,first_name,last_name,email', 'expert.user:id,first_name,last_name']);

        // Optional filtering by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by student name or expert name
        if ($search = $request->query('search')) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('expert.user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        // Map to a cleaner structure for the frontend
        $data = $bookings->through(function ($booking) {
            return [
                'id' => $booking->id,
                'student_name' => $booking->student ? $booking->student->first_name . ' ' . $booking->student->last_name : 'Unknown',
                'student_email' => $booking->student ? $booking->student->email : '',
                'expert_name' => ($booking->expert && $booking->expert->user) ? $booking->expert->user->first_name . ' ' . $booking->expert->user->last_name : 'Unknown',
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'amount' => $booking->amount,
                'status' => $booking->status,
                'meeting_link' => $booking->meeting_link,
                'created_at' => $booking->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ]
        ]);
    }
}
