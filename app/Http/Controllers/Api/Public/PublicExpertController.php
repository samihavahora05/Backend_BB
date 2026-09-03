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
            $query = ExpertProfile::with(['user:id,first_name,last_name,email,phone'])
                ->select(['id', 'user_id', 'designation', 'company', 'specialization', 'hourly_rate', 'average_rating', 'total_reviews', 'profile_photo', 'is_available', 'experience_years'])
                ->where(function($q) {
                    $q->where('is_available', true)
                      ->orWhereNull('is_available');
                })
                ->whereHas('user', fn($q) => $q->whereIn('status', ['active', 'Active', 'ACTIVE', 'pending', 'Pending', 'PENDING'])->orWhereNull('status')->whereNull('deleted_at'));

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

            $perPage = min((int)$request->query('per_page', 12), 100);
            $experts = $query->paginate($perPage);

            $data = $experts->through(function($e) {
                $rawPhoto = $e->profile_photo;
                $avatarUrl = null;
                if ($rawPhoto) {
                    if (str_starts_with($rawPhoto, 'http://') || str_starts_with($rawPhoto, 'https://') || str_starts_with($rawPhoto, 'data:')) {
                        $avatarUrl = $rawPhoto;
                    } elseif (str_starts_with($rawPhoto, '/uploads/') || str_starts_with($rawPhoto, 'uploads/')) {
                        $avatarUrl = str_starts_with($rawPhoto, '/') ? $rawPhoto : '/' . $rawPhoto;
                    } elseif (str_starts_with($rawPhoto, 'storage/') || str_starts_with($rawPhoto, '/storage/')) {
                        $avatarUrl = '/' . ltrim($rawPhoto, '/');
                    } else {
                        $avatarUrl = '/storage/' . ltrim($rawPhoto, '/');
                    }
                }

                $firstName = $e->user?->first_name ?? '';
                $lastName = $e->user?->last_name ?? '';
                $fullName = trim($firstName . ' ' . $lastName);

                return [
                    'id'             => $e->id,
                    'user_id'        => $e->user_id,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'name'           => !empty($fullName) ? $fullName : 'Expert User',
                    'email'          => $e->user?->email ?? '',
                    'phone'          => $e->user?->phone ?? '',
                    'avatar'         => $avatarUrl,
                    'profile_photo'  => $avatarUrl,
                    'designation'    => $e->designation,
                    'company'        => $e->company,
                    'specialization' => $e->specialization,
                    'hourly_rate'    => (float)($e->hourly_rate ?? 1500),
                    'average_rating' => (float)($e->average_rating ?? 5.0),
                    'total_reviews'  => (int)($e->total_reviews ?? 0),
                    'is_available'   => (bool)($e->is_available ?? true),
                ];
            });

            return [
                'success'    => true,
                'data'       => $data->items(),
                'pagination' => [
                    'current_page' => $experts->currentPage(),
                    'last_page'    => $experts->lastPage(),
                    'per_page'     => $experts->perPage(),
                    'total'        => $experts->total(),
                ]
            ];
        });

        return response()->json($responsePayload);
    }

    /**
     * Expert details, sessions, and upcoming availability
     * GET /api/public/experts/{id}
     */
    public function show($id)
    {
        $expert = ExpertProfile::with([
            'user:id,first_name,last_name,email,phone',
            'sessions' => fn($q) => $q->where('is_active', true),
            'availabilities' => fn($q) => $q->where('is_active', true)
        ])
        ->where(function($q) {
            $q->where('is_available', true)
              ->orWhereNull('is_available');
        })
        ->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('user_id', $id);
        })
        ->firstOrFail();

        $rawPhoto = $expert->profile_photo;
        $avatarUrl = null;
        if ($rawPhoto) {
            if (str_starts_with($rawPhoto, 'http://') || str_starts_with($rawPhoto, 'https://') || str_starts_with($rawPhoto, 'data:')) {
                $avatarUrl = $rawPhoto;
            } elseif (str_starts_with($rawPhoto, '/uploads/') || str_starts_with($rawPhoto, 'uploads/')) {
                $avatarUrl = str_starts_with($rawPhoto, '/') ? $rawPhoto : '/' . $rawPhoto;
            } elseif (str_starts_with($rawPhoto, 'storage/') || str_starts_with($rawPhoto, '/storage/')) {
                $avatarUrl = '/' . ltrim($rawPhoto, '/');
            } else {
                $avatarUrl = '/storage/' . ltrim($rawPhoto, '/');
            }
        }

        $firstName = $expert->user?->first_name ?? '';
        $lastName = $expert->user?->last_name ?? '';
        $fullName = trim($firstName . ' ' . $lastName);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $expert->id,
                'user_id'        => $expert->user_id,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'name'           => !empty($fullName) ? $fullName : 'Expert User',
                'email'          => $expert->user?->email ?? '',
                'phone'          => $expert->user?->phone ?? '',
                'avatar'         => $avatarUrl,
                'profile_photo'  => $avatarUrl,
                'designation'    => $expert->designation,
                'company'        => $expert->company,
                'bio'            => $expert->bio,
                'specialization' => $expert->specialization,
                'hourly_rate'    => (float)($expert->hourly_rate ?? 1500),
                'linkedin_url'   => $expert->linkedin_url,
                'average_rating' => (float)($expert->average_rating ?? 5.0),
                'total_reviews'  => (int)($expert->total_reviews ?? 0),
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
        try {
            $startTime = !empty($data['start_time']) ? \Carbon\Carbon::parse($data['start_time'])->format('H:i:s') : '10:00:00';
        } catch (\Exception $e) {
            $startTime = '10:00:00';
        }
        try {
            $endTime = !empty($data['end_time']) ? \Carbon\Carbon::parse($data['end_time'])->format('H:i:s') : '11:00:00';
        } catch (\Exception $e) {
            $endTime = '11:00:00';
        }

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

    /**
     * Get real database reviews for an expert
     * GET /api/public/experts/{id}/reviews
     */
    public function getReviews(Request $request, $id)
    {
        $expert = ExpertProfile::where('id', $id)->orWhere('user_id', $id)->first();
        if (!$expert) {
            return response()->json(['success' => false, 'message' => 'Expert not found'], 404);
        }

        $reviews = \App\Models\ExpertReview::where(function($q) use ($expert) {
                $q->where('expert_id', $expert->id)
                  ->orWhere('expert_id', $expert->user_id);
            })
            ->where(function($q) {
                $q->where('is_approved', true)->orWhereNull('is_approved');
            })
            ->latest()
            ->get();

        $avg = $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : (float)($expert->average_rating ?? 5.0);
        $total = $reviews->count() > 0 ? $reviews->count() : (int)($expert->total_reviews ?? 0);

        return response()->json([
            'success' => true,
            'data'    => $reviews,
            'meta'    => [
                'average_rating' => (float)$avg,
                'total_reviews'  => (int)$total,
            ]
        ]);
    }

    /**
     * Submit or update a real database review for an expert
     * POST /api/public/experts/{id}/reviews
     */
    public function storeReview(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated. Please log in to submit a review.'], 401);
        }

        $request->validate([
            'rating'        => 'required|numeric|min:1|max:5',
            'review_text'   => 'required|string|min:3|max:2000',
            'session_title' => 'nullable|string|max:255',
        ]);

        $expert = ExpertProfile::where('id', $id)->orWhere('user_id', $id)->first();
        if (!$expert) {
            return response()->json(['success' => false, 'message' => 'Expert profile not found.'], 404);
        }

        if ($expert->user_id === $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot review your own profile.'], 422);
        }

        // Sanitize review comment to prevent XSS
        $cleanText = strip_tags(trim($request->review_text));
        $sessionTitle = strip_tags(trim($request->input('session_title', '1:1 Mentorship Session')));

        // Check for existing review by this user (prevent duplicates)
        $existing = \App\Models\ExpertReview::where(function($q) use ($expert) {
                $q->where('expert_id', $expert->id)
                  ->orWhere('expert_id', $expert->user_id);
            })
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('student_id', $user->id);
            })
            ->first();

        $expertTargetId = $expert->user_id ?: $expert->id;

        if ($existing) {
            $existing->update([
                'expert_id'     => $expertTargetId,
                'rating'        => (float)$request->rating,
                'review_text'   => $cleanText,
                'session_title' => $sessionTitle,
                'is_approved'   => true,
            ]);
            $review = $existing;
            $message = 'Your review has been updated successfully!';
        } else {
            $review = \App\Models\ExpertReview::create([
                'expert_id'     => $expertTargetId,
                'user_id'       => $user->id,
                'student_id'    => $user->id,
                'rating'        => (float)$request->rating,
                'review_text'   => $cleanText,
                'session_title' => $sessionTitle,
                'is_approved'   => true,
            ]);
            $message = 'Thank you! Your review has been saved successfully.';
        }

        // Recalculate and update ExpertProfile stats
        $allReviews = \App\Models\ExpertReview::where(function($q) use ($expert) {
            $q->where('expert_id', $expert->id)->orWhere('expert_id', $expert->user_id);
        })->where('is_approved', true)->get();

        $newAvg = $allReviews->count() > 0 ? round($allReviews->avg('rating'), 1) : 5.0;
        $newTotal = $allReviews->count();

        $expert->update([
            'average_rating' => $newAvg,
            'total_reviews'  => $newTotal,
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $review,
            'meta'    => [
                'average_rating' => (float)$newAvg,
                'total_reviews'  => (int)$newTotal,
            ]
        ], 201);
    }
}
