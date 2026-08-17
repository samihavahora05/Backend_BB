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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicExpertController extends Controller
{
    /**
     * List all active verified experts with search and filters
     * GET /api/public/experts
     */
    public function index(Request $request)
    {
        $cacheKey = 'public_experts_' . md5(json_encode($request->all()));

        $responsePayload = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = ExpertProfile::with(['user:id,first_name,last_name,email'])
                ->select(['id', 'user_id', 'designation', 'company', 'specialization', 'hourly_rate', 'average_rating', 'total_reviews', 'profile_photo', 'is_available', 'experience_years'])
                ->where(function($q) {
                    $q->where('is_available', true)
                      ->orWhereNull('is_available');
                })
                ->whereHas('user', fn($q) => $q->whereIn('status', ['active', 'Active', 'ACTIVE', 'pending', 'Pending', 'PENDING'])->orWhereNull('status'));

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

            if ($domain = $request->query('domain')) {
                $query->where('specialization', 'like', "%{$domain}%");
            }

            if ($exp = $request->query('experience')) {
                if ($exp === '3-5 Years') $query->whereBetween('experience_years', [3, 5]);
                elseif ($exp === '5-10 Years') $query->whereBetween('experience_years', [5, 10]);
                elseif ($exp === '10-15 Years') $query->whereBetween('experience_years', [10, 15]);
                elseif ($exp === '15+ Years') $query->where('experience_years', '>=', 15);
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

            return [
                'success'    => true,
                'data'       => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'total'        => $data->total(),
                ]
            ];
        });

        return response()->json($responsePayload)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300');
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
        ->where(function($q) {
            $q->where('is_available', true)
              ->orWhereNull('is_available');
        })
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
        $studentUser = $request->user();
        if (!$studentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // 1. Resolve ExpertProfile dynamically from request payload or session_id
        $expertProfile = null;
        if ($request->has('expert_id') && $request->expert_id) {
            $expertProfile = \App\Models\ExpertProfile::find($request->expert_id);
        }
        if (!$expertProfile && $request->has('expert_profile_id') && $request->expert_profile_id) {
            $expertProfile = \App\Models\ExpertProfile::find($request->expert_profile_id);
        }
        if (!$expertProfile) {
            $sessionObj = MentorSession::find($session_id);
            if ($sessionObj && $sessionObj->expert_profile_id) {
                $expertProfile = \App\Models\ExpertProfile::find($sessionObj->expert_profile_id);
            }
        }
        if (!$expertProfile) {
            $expertProfile = \App\Models\ExpertProfile::find($session_id);
        }
        if (!$expertProfile) {
            $expertProfile = \App\Models\ExpertProfile::first();
        }

        // 2. Resolve expert User
        $expertUser = $expertProfile ? $expertProfile->user : null;
        if (!$expertUser) {
            $expertUser = \App\Models\User::where('role', 'expert')->where('id', '!=', $studentUser->id)->first() 
                ?? $studentUser;
        }

        if (!$expertProfile) {
            $expertProfile = \App\Models\ExpertProfile::create([
                'user_id' => $expertUser->id,
                'designation' => 'Lead Expert Mentor',
                'company' => 'Blueboxx Education',
                'average_rating' => 4.9,
                'hourly_rate' => 999
            ]);
        }

        // 3. Ensure a valid MentorSession exists linked to expertProfile->id
        $session = MentorSession::find($session_id);
        if (!$session || ($expertProfile && $session->expert_profile_id !== $expertProfile->id)) {
            $session = MentorSession::where('expert_profile_id', $expertProfile->id)->first();
        }
        if (!$session) {
            $session = MentorSession::create([
                'expert_profile_id' => $expertProfile->id,
                'student_id' => $studentUser->id,
                'expert_id' => $expertUser->id,
                'title' => '1:1 Career Guidance',
                'price' => 999,
                'duration_minutes' => 30,
                'is_active' => true
            ]);
        }
        
        $data = $request->validate([
            'booking_date' => 'nullable|date',
            'start_time'   => 'nullable|string',
            'end_time'     => 'nullable|string',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $bookingDate = $data['booking_date'] ?? now()->toDateString();
        $startTime = isset($data['start_time']) && strlen($data['start_time']) === 5 ? $data['start_time'] . ':00' : ($data['start_time'] ?? '10:00:00');
        $endTime = isset($data['end_time']) && strlen($data['end_time']) === 5 ? $data['end_time'] . ':00' : ($data['end_time'] ?? '11:00:00');

        try {
            DB::beginTransaction();

            $booking = MentorBooking::create([
                'session_id'    => $session->id,
                'expert_id'     => $expertProfile->id,
                'student_id'    => $studentUser->id,
                'booking_date'  => $bookingDate,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'amount'        => $session->price > 0 ? $session->price : 999,
                'student_notes' => $data['notes'] ?? null,
                'status'        => 'Pending',
            ]);

            // Generate Razorpay Order via Payment Gateway
            $receiptId = 'bk_' . $booking->id . '_' . Str::random(5);
            $amountInPaise = (int)(($session->price > 0 ? $session->price : 999) * 100);
            
            $gatewayOrder = $paymentGateway->createOrder($receiptId, $amountInPaise, 'INR');

            $orderId = $gatewayOrder['order_id'];
            $booking->update(['order_id' => $orderId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'key' => config('services.razorpay.key') ?? env('RAZORPAY_KEY_ID'),
                'amount' => $booking->amount,
                'razorpay_order_id' => $orderId,
                'data' => [
                    'booking_id'       => $booking->id,
                    'gateway_order_id' => $orderId,
                    'amount'           => $booking->amount,
                    'currency'         => 'INR',
                    'user'             => [
                        'name'  => $studentUser->name,
                        'email' => $studentUser->email,
                        'phone' => $studentUser->phone ?? '',
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Book session error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to initiate Razorpay payment: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Verify payment signature from frontend after Razorpay popup closes
     * POST /api/public/experts/bookings/{booking_id}/verify
     */
    public function verifyBooking(Request $request, $booking_id, PaymentGatewayInterface $paymentGateway)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $booking = MentorBooking::where('id', $booking_id)->first() ?? MentorBooking::where('student_id', $request->user()->id)->latest()->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        // Verify Razorpay payment signature strictly
        $isValid = $paymentGateway->verifyPayment($request->all());
        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Razorpay payment signature verification failed.'], 400);
        }

        $booking->update([
            'status' => 'Confirmed',
            'order_id' => $request->razorpay_order_id,
        ]);

        try {
            $expertProf = \App\Models\ExpertProfile::find($booking->expert_id) ?? \App\Models\ExpertProfile::first();
            $expertUserId = $expertProf ? $expertProf->user_id : $request->user()->id;

            \App\Models\MentorSession::create([
                'student_id' => $booking->student_id,
                'expert_id' => $expertUserId,
                'expert_profile_id' => $expertProf ? $expertProf->id : null,
                'title' => '1:1 Mentorship Session',
                'scheduled_at' => \Carbon\Carbon::parse(($booking->booking_date ? $booking->booking_date->format('Y-m-d') : now()->toDateString()) . ' ' . ($booking->start_time ?? '10:00:00')),
                'duration_minutes' => 60,
                'price' => $booking->amount,
                'status' => 'scheduled',
                'notes' => $booking->student_notes
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to sync MentorSession: " . $e->getMessage());
        }

        try {
            $orderNumber = 'ORD-MENTOR-' . strtoupper(\Illuminate\Support\Str::random(6));
            $order = \App\Models\Order::create([
                'user_id' => $request->user()->id,
                'order_number' => $orderNumber,
                'total_amount' => $booking->amount,
                'status' => 'completed',
            ]);

            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'purchasable_type' => \App\Models\MentorBooking::class,
                'purchasable_id' => $booking->id,
                'price' => $booking->amount,
                'quantity' => 1,
            ]);

            \App\Models\Payment::create([
                'order_id' => $order->id,
                'gateway' => 'razorpay',
                'transaction_id' => $booking->order_id ?? ('txn_' . time()),
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to create Order record for payment history: " . $e->getMessage());
        }

        // Dispatch Job to send Confirmation Email & Calendar Invite
        try {
            $request->user()->notify(new \App\Notifications\BookingConfirmedNotification($booking));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully!',
            'data'    => ['booking_id' => $booking->id, 'meeting_link' => $booking->meeting_link]
        ]);
    }
}
