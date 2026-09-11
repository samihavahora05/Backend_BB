<?php

namespace App\Http\Controllers\Api\Expert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MentorSession;
use App\Models\MentorBooking;
use App\Models\ExpertProfile;
use App\Models\ExpertAvailability;
use App\Models\ExpertReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpertDashboardController extends Controller
{
    /**
     * Resolve authenticated expert context (User ID, ExpertProfile ID, and Model)
     */
    protected function getExpertContext(Request $request): array
    {
        $user = $request->user();
        $expertUserId = $user->id;
        
        $expertProfile = $user->expertProfile 
            ?? ExpertProfile::where('user_id', $user->id)->first();
            
        $expertProfileId = $expertProfile?->id;

        return [$expertUserId, $expertProfileId, $expertProfile];
    }

    /**
     * Get expert dashboard metrics
     */
    public function metrics(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        // 1. Active mentees: distinct students from bookings or sessions for this expert
        $bookingStudentIds = MentorBooking::where('expert_id', $expertProfileId)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->pluck('student_id');

        $sessionStudentIds = MentorSession::where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->whereIn('status', ['completed', 'scheduled', 'confirmed'])
            ->whereNotNull('student_id')
            ->pluck('student_id');

        $activeMentees = $bookingStudentIds->merge($sessionStudentIds)->unique()->filter()->count();

        // 2. Hours mentored
        $sessionMinutes = MentorSession::where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->where('status', 'completed')
            ->sum('duration_minutes');

        $bookingCompletedCount = MentorBooking::where('expert_id', $expertProfileId)
            ->where('status', 'Completed')
            ->count();

        $totalMinutes = $sessionMinutes + ($bookingCompletedCount * 60);
        $hoursMentored = round($totalMinutes / 60, 1);

        // 3. Earnings / Pending Payout (from bookings amount)
        $bookingEarnings = (float)MentorBooking::where('expert_id', $expertProfileId)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->sum('amount');

        $sessionEarnings = (float)MentorSession::where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->whereIn('status', ['completed', 'scheduled', 'confirmed'])
            ->sum('price');

        $pendingPayout = $bookingEarnings > 0 ? $bookingEarnings : $sessionEarnings;

        // 4. Average Rating
        $averageRating = 0;
        if ($expertProfileId || $expertUserId) {
            $averageRating = ExpertReview::where(function ($q) use ($expertProfileId, $expertUserId) {
                    if ($expertProfileId) $q->where('expert_id', $expertProfileId);
                    if ($expertUserId) $q->orWhere('expert_id', $expertUserId);
                })
                ->where(function ($q) {
                    $q->where('is_approved', true)->orWhereNull('is_approved');
                })
                ->avg('rating') ?? 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'active_mentees' => $activeMentees,
                'hours_mentored' => $hoursMentored,
                'pending_payout' => round($pendingPayout, 2),
                'average_rating' => round((float)$averageRating, 1)
            ]
        ]);
    }

    /**
     * Get upcoming sessions for the authenticated expert
     */
    public function upcomingSessions(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        // 1. Fetch from MentorBooking
        $bookings = MentorBooking::with('student')
            ->where('expert_id', $expertProfileId)
            ->whereIn('status', ['Confirmed', 'Pending'])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(10)
            ->get()
            ->map(function ($b) {
                $studentName = $b->student?->name ?? 'Student';
                $bookingDate = $b->booking_date ? Carbon::parse($b->booking_date->format('Y-m-d') . ' ' . ($b->start_time ?? '10:00:00')) : now();
                return [
                    'id'           => $b->id,
                    'booking_id'   => $b->id,
                    'mentee'       => $studentName,
                    'student_email'=> $b->student?->email ?? '',
                    'time'         => $bookingDate->format('M d, g:i A'),
                    'topic'        => $b->student_notes ?? '1:1 Mentorship Session',
                    'meeting_link' => $b->meeting_link,
                    'status'       => $b->status,
                    'amount'       => (float)$b->amount,
                ];
            });

        // 2. Fetch from MentorSession
        $sessions = MentorSession::with('student')
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('scheduled_at', '>=', now())
            ->whereNotNull('student_id')
            ->orderBy('scheduled_at', 'asc')
            ->take(10)
            ->get()
            ->map(function ($session) {
                return [
                    'id'           => $session->id,
                    'mentee'       => $session->student->name ?? 'Mentee',
                    'student_email'=> $session->student->email ?? '',
                    'time'         => $session->scheduled_at ? $session->scheduled_at->format('M d, g:i A') : 'TBD',
                    'topic'        => $session->notes ?? $session->title ?? 'Mentorship Session',
                    'meeting_link' => $session->meeting_url ?? $session->meeting_link,
                    'status'       => $session->status,
                    'amount'       => (float)$session->price,
                ];
            });

        $merged = collect($bookings)->concat($sessions)->sortBy('time')->values()->take(10);

        return response()->json([
            'success' => true,
            'data' => $merged
        ]);
    }

    /**
     * Get all bookings belonging to the authenticated expert
     * GET /api/expert/bookings
     */
    public function bookings(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $bookings = MentorBooking::with(['student.studentProfile', 'session'])
            ->where('expert_id', $expertProfileId)
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'id'            => $b->id,
                    'expert_id'     => $b->expert_id,
                    'student'       => [
                        'id'      => $b->student?->id,
                        'name'    => $b->student?->name ?? 'Student',
                        'email'   => $b->student?->email,
                        'phone'   => $b->student?->phone ?? '',
                        'college' => $b->student?->studentProfile?->college_name,
                        'course'  => $b->student?->studentProfile?->course,
                    ],
                    'session_title' => $b->session?->title ?? '1:1 Mentorship Session',
                    'date'          => $b->booking_date ? $b->booking_date->format('Y-m-d') : null,
                    'start_time'    => $b->start_time,
                    'end_time'      => $b->end_time,
                    'amount'        => (float)$b->amount,
                    'status'        => $b->status,
                    'meeting_link'  => $b->meeting_link,
                    'notes'         => $b->student_notes,
                    'created_at'    => $b->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }

    /**
     * Get a specific booking for the authenticated expert (Strict IDOR protection)
     * GET /api/expert/bookings/{id}
     */
    public function showBooking(Request $request, $id)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $booking = MentorBooking::with(['student.studentProfile', 'session'])
            ->where('id', $id)
            ->where('expert_id', $expertProfileId)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you are not authorized to view this booking.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $booking->id,
                'expert_id'     => $booking->expert_id,
                'student'       => [
                    'id'      => $booking->student?->id,
                    'name'    => $booking->student?->name ?? 'Student',
                    'email'   => $booking->student?->email,
                    'phone'   => $booking->student?->phone ?? '',
                    'college' => $booking->student?->studentProfile?->college_name,
                    'course'  => $booking->student?->studentProfile?->course,
                ],
                'session_title' => $booking->session?->title ?? '1:1 Mentorship Session',
                'date'          => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                'start_time'    => $booking->start_time,
                'end_time'      => $booking->end_time,
                'amount'        => (float)$booking->amount,
                'status'        => $booking->status,
                'meeting_link'  => $booking->meeting_link,
                'notes'         => $booking->student_notes,
                'created_at'    => $booking->created_at?->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update booking status with strict ownership enforcement
     * PUT /api/expert/bookings/{id}/status
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Confirmed,Completed,Cancelled'
        ]);

        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $booking = MentorBooking::where('id', $id)
            ->where('expert_id', $expertProfileId)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or unauthorized.'
            ], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => "Booking status updated to {$booking->status}.",
            'data'    => $booking
        ]);
    }

    /**
     * Get earnings chart data (Last 6 Months)
     */
    public function earningsChart(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('M');
            $yearMonth = $monthDate->format('Y-m');

            $amount = (float)MentorBooking::where('expert_id', $expertProfileId)
                ->whereIn('status', ['Confirmed', 'Completed'])
                ->whereRaw("strftime('%Y-%m', booking_date) = ?", [$yearMonth])
                ->sum('amount');

            $months[] = [
                'name' => $monthKey,
                'value' => round($amount, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $months
        ]);
    }

    /**
     * Get mentee requests for this expert
     */
    public function menteeRequests(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $bookingRequests = MentorBooking::with('student')
            ->where('expert_id', $expertProfileId)
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'id'      => $b->id,
                    'name'    => $b->student?->name ?? 'Student',
                    'reqType' => 'Requested a session on ' . ($b->booking_date ? $b->booking_date->format('M d') : 'Upcoming'),
                    'time'    => $b->start_time,
                    'notes'   => $b->student_notes,
                ];
            });

        $sessionRequests = MentorSession::with('student')
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->where('status', 'pending')
            ->whereNotNull('student_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'      => $s->id,
                    'name'    => $s->student->name ?? 'Student',
                    'reqType' => 'Requested a session for ' . ($s->scheduled_at ? $s->scheduled_at->format('M d') : 'Upcoming'),
                    'time'    => $s->scheduled_at ? $s->scheduled_at->format('h:i A') : 'TBD',
                    'notes'   => $s->notes,
                ];
            });

        $merged = collect($bookingRequests)->concat($sessionRequests)->values();

        return response()->json([
            'success' => true,
            'data' => $merged
        ]);
    }

    /**
     * Get expert transactions/payout history
     */
    public function transactions(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $bookings = MentorBooking::with('student')
            ->where('expert_id', $expertProfileId)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($b) {
                $monthStr = $b->booking_date ? $b->booking_date->format('M') : 'Oct';
                return [
                    'id'          => 'TXN-MB-' . str_pad($b->id, 5, '0', STR_PAD_LEFT),
                    'description' => 'Session with ' . ($b->student?->name ?? 'Student'),
                    'date'        => $b->booking_date ? $b->booking_date->format('M d, Y') : now()->format('M d, Y'),
                    'month'       => $monthStr,
                    'amount'      => (float)$b->amount,
                    'status'      => $b->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Get all unique mentees for this expert
     */
    public function mentees(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $menteesMap = [];

        // 1. From MentorBooking
        $bookings = MentorBooking::with(['student.studentProfile'])
            ->where('expert_id', $expertProfileId)
            ->get();

        foreach ($bookings as $b) {
            $student = $b->student;
            if (!$student) continue;

            if (!isset($menteesMap[$student->id])) {
                $menteesMap[$student->id] = [
                    'id'       => $student->id,
                    'name'     => $student->name,
                    'email'    => $student->email,
                    'role'     => $student->studentProfile->course ?? 'Software Engineer',
                    'company'  => $student->studentProfile->college_name ?? 'Student',
                    'status'   => $b->status === 'Completed' ? 'Completed' : 'Active',
                    'progress' => $b->status === 'Completed' ? 100 : 50,
                    'rating'   => '5.0',
                ];
            }
        }

        // 2. From MentorSession
        $sessions = MentorSession::with(['student.studentProfile'])
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->whereNotNull('student_id')
            ->get();

        foreach ($sessions as $session) {
            $student = $session->student;
            if (!$student) continue;

            if (!isset($menteesMap[$student->id])) {
                $menteesMap[$student->id] = [
                    'id'       => $student->id,
                    'name'     => $student->name,
                    'email'    => $student->email,
                    'role'     => $student->studentProfile->course ?? 'Software Engineer',
                    'company'  => $student->studentProfile->college_name ?? 'Student',
                    'status'   => $session->status === 'completed' ? 'Completed' : 'Active',
                    'progress' => $session->status === 'completed' ? 100 : 50,
                    'rating'   => '5.0',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($menteesMap)
        ]);
    }

    /**
     * Get all schedule / sessions for this expert
     */
    public function schedule(Request $request)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $schedule = collect();

        // 1. Bookings
        $bookings = MentorBooking::with('student')
            ->where('expert_id', $expertProfileId)
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($b) {
                $startTimeStr = $b->start_time ? Carbon::parse($b->start_time)->format('h:i A') : '10:00 AM';
                $endTimeStr = $b->end_time ? Carbon::parse($b->end_time)->format('h:i A') : '11:00 AM';
                return [
                    'id'           => 'bk_' . $b->id,
                    'mentee'       => $b->student?->name ?? 'Student',
                    'time'         => "{$startTimeStr} - {$endTimeStr}",
                    'type'         => $b->student_notes ?? '1:1 Mentorship Session',
                    'date'         => $b->booking_date ? $b->booking_date->format('Y-m-d') : null,
                    'booked'       => true,
                    'status'       => strtolower($b->status),
                    'meeting_link' => $b->meeting_link,
                ];
            });

        $schedule = $schedule->concat($bookings);

        // 2. Scheduled Sessions
        $sessions = MentorSession::with('student')
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) {
                    $q->orWhere('expert_profile_id', $expertProfileId);
                }
            })
            ->whereNotNull('student_id')
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => 'ses_' . $s->id,
                    'mentee'       => $s->student?->name ?? 'Unknown',
                    'time'         => $s->scheduled_at ? Carbon::parse($s->scheduled_at)->format('h:i A') . ' - ' . Carbon::parse($s->scheduled_at)->addMinutes($s->duration_minutes)->format('h:i A') : 'TBD',
                    'type'         => $s->notes ?? $s->title ?? 'Mentorship Session',
                    'date'         => $s->scheduled_at ? Carbon::parse($s->scheduled_at)->format('Y-m-d') : null,
                    'booked'       => true,
                    'status'       => $s->status,
                    'meeting_link' => $s->meeting_url ?? $s->meeting_link,
                ];
            });

        $schedule = $schedule->concat($sessions);

        // 3. Availability Slots
        if ($expertProfileId) {
            $availabilities = ExpertAvailability::where('expert_profile_id', $expertProfileId)
                ->where('is_active', true)
                ->get()
                ->map(function ($a) {
                    return [
                        'id'     => 'avail_' . $a->id,
                        'mentee' => null,
                        'time'   => Carbon::parse($a->start_time)->format('h:i A') . ' - ' . Carbon::parse($a->end_time)->format('h:i A'),
                        'type'   => 'Available Slot',
                        'date'   => Carbon::now()->next($a->day_of_week)->format('Y-m-d'),
                        'booked' => false,
                        'status' => 'available',
                    ];
                });

            $schedule = $schedule->concat($availabilities);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule->values()
        ]);
    }

    /**
     * Add availability slot for expert
     * POST /api/expert/schedule
     */
    public function addAvailability(Request $request)
    {
        $request->validate([
            'date'       => 'nullable|date',
            'start'      => 'nullable|string',
            'end'        => 'nullable|string',
            'start_time' => 'nullable|string',
            'end_time'   => 'nullable|string',
        ]);

        [$expertUserId, $expertProfileId, $expertProfile] = $this->getExpertContext($request);

        if (!$expertProfile) {
            $expertProfile = ExpertProfile::firstOrCreate(
                ['user_id' => $expertUserId],
                ['designation' => 'Expert Mentor', 'average_rating' => 5.0, 'hourly_rate' => 999]
            );
            $expertProfileId = $expertProfile->id;
        }

        $dateStr = $request->date ?? now()->toDateString();
        $startTime = $request->start ?? $request->start_time ?? '10:00:00';
        $endTime = $request->end ?? $request->end_time ?? '11:00:00';
        $dayOfWeek = Carbon::parse($dateStr)->dayOfWeek;

        $slot = ExpertAvailability::create([
            'expert_profile_id' => $expertProfileId,
            'day_of_week'       => $dayOfWeek,
            'start_time'        => Carbon::parse($startTime)->format('H:i:s'),
            'end_time'          => Carbon::parse($endTime)->format('H:i:s'),
            'is_active'         => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Availability added successfully.',
            'data'    => $slot
        ], 201);
    }

    /**
     * Remove availability slot
     * DELETE /api/expert/schedule/{id}
     */
    public function removeAvailability(Request $request, $id)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        $cleanId = str_replace('avail_', '', $id);
        $slot = ExpertAvailability::where('id', $cleanId)
            ->where('expert_profile_id', $expertProfileId)
            ->first();

        if ($slot) {
            $slot->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Slot removed successfully.'
        ]);
    }

    /**
     * Accept a mentee session request (Strict IDOR protection)
     */
    public function acceptRequest(Request $request, $id)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        // Check MentorBooking first
        $booking = MentorBooking::where('id', $id)->where('expert_id', $expertProfileId)->first();
        if ($booking) {
            $booking->status = 'Confirmed';
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => 'Mentee booking accepted. Session confirmed.'
            ]);
        }

        // Check MentorSession
        $session = MentorSession::where('id', $id)
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) $q->orWhere('expert_profile_id', $expertProfileId);
            })
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session request not found or unauthorized.'
            ], 404);
        }

        $session->status = 'scheduled';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Mentee request accepted. Session scheduled.'
        ]);
    }

    /**
     * Decline a mentee session request (Strict IDOR protection)
     */
    public function declineRequest(Request $request, $id)
    {
        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        // Check MentorBooking
        $booking = MentorBooking::where('id', $id)->where('expert_id', $expertProfileId)->first();
        if ($booking) {
            $booking->status = 'Cancelled';
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => 'Mentee booking declined.'
            ]);
        }

        // Check MentorSession
        $session = MentorSession::where('id', $id)
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) $q->orWhere('expert_profile_id', $expertProfileId);
            })
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session request not found or unauthorized.'
            ], 404);
        }

        $session->status = 'cancelled';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Mentee request declined.'
        ]);
    }

    /**
     * Update meeting link for a session or booking (Strict IDOR protection)
     */
    public function updateMeetingLink(Request $request, $id)
    {
        $request->validate([
            'meeting_link' => 'required|url'
        ]);

        [$expertUserId, $expertProfileId] = $this->getExpertContext($request);

        // Update in MentorBooking if exists
        $booking = MentorBooking::where('id', $id)->where('expert_id', $expertProfileId)->first();
        if ($booking) {
            $booking->meeting_link = $request->meeting_link;
            $booking->save();
        }

        // Update in MentorSession
        $session = MentorSession::where('id', $id)
            ->where(function ($q) use ($expertUserId, $expertProfileId) {
                $q->where('expert_id', $expertUserId);
                if ($expertProfileId) $q->orWhere('expert_profile_id', $expertProfileId);
            })
            ->first();

        if ($session) {
            $session->meeting_url = $request->meeting_link;
            $session->meeting_link = $request->meeting_link;
            $session->save();
        }

        if (!$booking && !$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session or booking not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meeting link updated successfully.',
            'data'    => $booking ?? $session
        ]);
    }

    /**
     * Get authenticated expert's full profile and completion checklist
     * GET /api/expert/profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $profile = $user->expertProfile ?? ExpertProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'designation' => 'Expert',
                'company' => 'Independent',
                'specialization' => 'Career & Technical Mentorship',
                'hourly_rate' => 1500,
                'approval_status' => 'approved',
                'is_verified' => true,
                'is_available' => true,
                'average_rating' => 5.0,
                'total_reviews' => 0,
            ]
        );

        // Checklist evaluation
        $checklist = [
            ['key' => 'profile_photo', 'label' => 'Profile Photo', 'completed' => !empty($profile->profile_photo)],
            ['key' => 'first_name', 'label' => 'First Name', 'completed' => !empty($user->first_name)],
            ['key' => 'last_name', 'label' => 'Last Name', 'completed' => !empty($user->last_name)],
            ['key' => 'phone', 'label' => 'Phone Number', 'completed' => !empty($user->phone)],
            ['key' => 'designation', 'label' => 'Designation / Title', 'completed' => !empty($profile->designation)],
            ['key' => 'company', 'label' => 'Company / Organization', 'completed' => !empty($profile->company)],
            ['key' => 'specialization', 'label' => 'Specialization / Expertise', 'completed' => !empty($profile->specialization)],
            ['key' => 'hourly_rate', 'label' => 'Hourly Rate (₹)', 'completed' => !empty($profile->hourly_rate) && (float)$profile->hourly_rate > 0],
            ['key' => 'bio', 'label' => 'Bio / About', 'completed' => !empty($profile->bio)],
            ['key' => 'experience_years', 'label' => 'Experience (Years)', 'completed' => $profile->experience_years !== null && $profile->experience_years >= 0],
            ['key' => 'highest_qualification', 'label' => 'Highest Qualification', 'completed' => !empty($profile->highest_qualification)],
            ['key' => 'linkedin_url', 'label' => 'LinkedIn Profile', 'completed' => !empty($profile->linkedin_url)],
        ];

        $completedCount = count(array_filter($checklist, fn($item) => $item['completed']));
        $completionPercentage = (int)round(($completedCount / count($checklist)) * 100);

        if ($profile->profile_completion_percentage !== $completionPercentage) {
            $profile->profile_completion_percentage = $completionPercentage;
            $profile->saveQuietly();
        }

        $rawPhoto = $profile->profile_photo;
        $avatarUrl = null;
        if ($rawPhoto) {
            if (str_starts_with($rawPhoto, 'http://') || str_starts_with($rawPhoto, 'https://') || str_starts_with($rawPhoto, 'data:')) {
                $avatarUrl = $rawPhoto;
            } else {
                $avatarUrl = '/' . ltrim(str_starts_with($rawPhoto, 'storage/') || str_starts_with($rawPhoto, '/storage/') ? $rawPhoto : 'storage/' . $rawPhoto, '/');
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'profile' => [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'designation' => $profile->designation,
                    'company' => $profile->company,
                    'specialization' => $profile->specialization,
                    'hourly_rate' => (float)($profile->hourly_rate ?? 1500),
                    'bio' => $profile->bio,
                    'experience_years' => $profile->experience_years,
                    'highest_qualification' => $profile->highest_qualification,
                    'profile_photo' => $avatarUrl,
                    'avatar' => $avatarUrl,
                    'linkedin_url' => $profile->linkedin_url,
                    'github_url' => $profile->github_url,
                    'portfolio_url' => $profile->portfolio_url,
                    'website' => $profile->website,
                    'is_available' => (bool)($profile->is_available ?? true),
                    'is_verified' => (bool)($profile->is_verified ?? true),
                    'approval_status' => $profile->approval_status ?? 'approved',
                    'average_rating' => (float)($profile->average_rating ?? 5.0),
                    'total_reviews' => (int)($profile->total_reviews ?? 0),
                ],
                'completion' => [
                    'percentage' => $completionPercentage,
                    'completed_count' => $completedCount,
                    'total_count' => count($checklist),
                    'checklist' => $checklist,
                ]
            ]
        ]);
    }

    /**
     * Update authenticated expert's profile fields
     * PUT /api/expert/profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name'            => 'nullable|string|max:100',
            'last_name'             => 'nullable|string|max:100',
            'phone'                 => 'nullable|string|max:30',
            'designation'           => 'nullable|string|max:150',
            'company'               => 'nullable|string|max:150',
            'specialization'        => 'nullable|string|max:255',
            'hourly_rate'           => 'nullable|numeric|min:0|max:100000',
            'bio'                   => 'nullable|string|max:5000',
            'experience_years'      => 'nullable|integer|min:0|max:70',
            'highest_qualification' => 'nullable|string|max:150',
            'linkedin_url'          => 'nullable|url|max:255',
            'github_url'            => 'nullable|url|max:255',
            'portfolio_url'         => 'nullable|url|max:255',
            'website'               => 'nullable|url|max:255',
            'is_available'          => 'nullable|boolean',
        ]);

        $user = $request->user() ?? auth()->user();

        DB::transaction(function () use ($request, $user) {
            // Update User basics
            $userUpdates = [];
            if ($request->has('first_name')) $userUpdates['first_name'] = trim((string)$request->first_name);
            if ($request->has('last_name')) $userUpdates['last_name'] = trim((string)$request->last_name);
            if ($request->has('first_name') || $request->has('last_name')) {
                $firstName = $userUpdates['first_name'] ?? $user->first_name;
                $lastName = $userUpdates['last_name'] ?? $user->last_name;
                $userUpdates['name'] = trim($firstName . ' ' . $lastName) ?: 'Expert User';
            }
            if ($request->has('phone')) {
                $userUpdates['phone'] = trim((string)$request->phone);
            }
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Update Profile fields
            $profile = $user->expertProfile ?? ExpertProfile::firstOrCreate(['user_id' => $user->id]);

            $profileFields = [
                'designation', 'company', 'specialization', 'hourly_rate',
                'bio', 'experience_years', 'highest_qualification',
                'linkedin_url', 'github_url', 'portfolio_url', 'website',
                'is_available'
            ];

            $profileUpdates = [];
            foreach ($profileFields as $field) {
                if ($request->has($field)) {
                    $val = $request->input($field);
                    if ($field === 'hourly_rate') {
                        $profileUpdates[$field] = is_numeric($val) ? (float)$val : null;
                    } elseif ($field === 'experience_years') {
                        $profileUpdates[$field] = is_numeric($val) ? (int)$val : null;
                    } elseif ($field === 'is_available') {
                        $profileUpdates[$field] = (bool)$val;
                    } else {
                        $profileUpdates[$field] = $val !== null ? trim((string)$val) : null;
                    }
                }
            }

            if (!empty($profileUpdates)) {
                $profile->update($profileUpdates);
            }
        });

        \Illuminate\Support\Facades\Cache::flush();

        return $this->getProfile($request);
    }

    /**
     * Upload and update authenticated expert's profile photo
     * POST /api/expert/profile/photo
     */
    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
        ]);

        $user = $request->user() ?? auth()->user();
        $profile = $user->expertProfile ?? ExpertProfile::firstOrCreate(['user_id' => $user->id]);

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = 'exp_avatar_' . $user->id . '_' . time() . '.' . $extension;
        
        $path = $file->storeAs('avatars', $fileName, 'public');
        $photoUrl = '/storage/' . $path;

        // Delete old photo file if it was a local storage path
        if ($profile->profile_photo && str_contains($profile->profile_photo, '/storage/avatars/')) {
            $oldRel = ltrim(substr($profile->profile_photo, strpos($profile->profile_photo, '/storage/') + 9), '/');
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldRel)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldRel);
            }
        }

        $profile->profile_photo = $photoUrl;
        $profile->save();

        \Illuminate\Support\Facades\Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'data' => [
                'avatar' => $photoUrl,
                'profile_photo' => $photoUrl,
            ]
        ]);
    }
}

