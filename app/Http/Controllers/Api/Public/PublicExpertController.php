<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ExpertProfile;
use App\Models\ExpertAvailability;
use App\Models\MentorSession;
use App\Models\MentorBooking;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicExpertController extends Controller
{
    /**
     * List all active verified experts with search and filters
     * GET /api/public/experts
     */
    public function index(Request $request)
    {
        $query = ExpertProfile::with(['user:id,first_name,last_name,email'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->whereHas('user', fn($q) => $q->where('status', 'active'));

        if ($s = $request->query('search')) {
            $query->where(function($q) use ($s) {
                $q->whereHas('user', fn($qu) => $qu->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"))
                  ->orWhere('designation', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%");
            });
        }

        // Filters
        if ($spec = $request->query('specialization')) {
            $query->where('specialization', 'like', "%{$spec}%");
        }

        $sort = $request->query('sort', 'rating_high');
        if ($sort === 'rating_high') {
            $query->orderByDesc('average_rating');
        } elseif ($sort === 'price_low') {
            $query->orderBy('hourly_rate');
        } elseif ($sort === 'price_high') {
            $query->orderByDesc('hourly_rate');
        }

        $perPage = min((int)$request->query('per_page', 12), 50);
        $experts = $query->paginate($perPage);

        $data = $experts->through(fn($e) => [
            'id'             => $e->id,
            'name'           => $e->user ? $e->user->first_name . ' ' . $e->user->last_name : 'Expert User',
            'avatar'         => $e->profile_photo ? url('storage/' . $e->profile_photo) : null,
            'designation'    => $e->designation,
            'company'        => $e->company,
            'specialization' => $e->specialization,
            'hourly_rate'    => $e->hourly_rate,
            'average_rating' => $e->average_rating,
            'total_reviews'  => $e->total_reviews,
        ]);

        return response()->json([
            'success'    => true,
            'data'       => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Expert details, sessions, and upcoming availability
     * GET /api/public/experts/{id}
     */
    public function show($id)
    {
        $expert = ExpertProfile::with([
            'user:id,first_name,last_name,email',
            'sessions' => fn($q) => $q->where('is_active', true),
            'availabilities' => fn($q) => $q->where('is_active', true)
        ])
        ->where('is_available', true)
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $expert->id,
                'name'           => $expert->user ? $expert->user->first_name . ' ' . $expert->user->last_name : 'Expert User',
                'avatar'         => $expert->profile_photo ? url('storage/' . $expert->profile_photo) : null,
                'designation'    => $expert->designation,
                'company'        => $expert->company,
                'bio'            => $expert->bio,
                'specialization' => $expert->specialization,
                'hourly_rate'    => $expert->hourly_rate,
                'linkedin_url'   => $expert->linkedin_url,
                'average_rating' => $expert->average_rating,
                'total_reviews'  => $expert->total_reviews,
                'sessions'       => $expert->sessions,
                'availability'   => $expert->availabilities,
            ]
        ]);
    }

    /**
     * Create a booking order for a session via Razorpay
     * POST /api/public/experts/sessions/{session_id}/book
     */
    public function bookSession(Request $request, $session_id, PaymentGatewayInterface $paymentGateway)
    {
        $session = MentorSession::findOrFail($session_id);
        
        $data = $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'notes'        => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $booking = MentorBooking::create([
                'session_id'    => $session->id,
                'expert_id'     => $session->expert_profile_id,
                'student_id'    => $request->user()->id,
                'booking_date'  => $data['booking_date'],
                'start_time'    => $data['start_time'],
                'end_time'      => $data['end_time'],
                'amount'        => $session->price,
                'student_notes' => $data['notes'] ?? null,
                'status'        => 'Pending',
            ]);

            // If session is free, confirm immediately
            if ($session->price <= 0) {
                $booking->update(['status' => 'Confirmed']);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Booking confirmed automatically (Free Session).',
                    'data'    => ['booking_id' => $booking->id, 'gateway_order_id' => null, 'amount' => 0]
                ]);
            }

            // Generate Razorpay Order via Payment Gateway
            $receiptId = 'bk_' . $booking->id . '_' . Str::random(5);
            $gatewayOrder = $paymentGateway->createOrder($session->price, 'INR', $receiptId);

            $booking->update(['order_id' => $gatewayOrder['id']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => [
                    'booking_id'       => $booking->id,
                    'gateway_order_id' => $gatewayOrder['id'],
                    'amount'           => $session->price,
                    'currency'         => 'INR',
                    'user'             => [
                        'name'  => $request->user()->name,
                        'email' => $request->user()->email,
                        'phone' => $request->user()->phone ?? '',
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to initiate booking.'], 500);
        }
    }

    /**
     * Verify payment signature from frontend after Razorpay popup closes
     * POST /api/public/experts/bookings/{booking_id}/verify
     */
    public function verifyBooking(Request $request, $booking_id, PaymentGatewayInterface $paymentGateway)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $booking = MentorBooking::where('id', $booking_id)
            ->where('student_id', $request->user()->id)
            ->where('status', 'Pending')
            ->firstOrFail();

        // Verify Razorpay signature
        $isValid = $paymentGateway->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 400);
        }

        // Mark as Confirmed and generate Meeting Link (Dummy/Zoom)
        $booking->update([
            'status' => 'Confirmed',
            'meeting_link' => 'https://meet.google.com/xyz-abcd-123' // Or integrate Zoom API here
        ]);

        // Dispatch Job to send Confirmation Email & Calendar Invite
        $request->user()->notify(new \App\Notifications\BookingConfirmedNotification($booking));

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully!',
            'data'    => ['booking_id' => $booking->id, 'meeting_link' => $booking->meeting_link]
        ]);
    }
}
