<?php

namespace App\Http\Controllers\Api\Expert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MentorSession;
use Illuminate\Support\Facades\DB;

class ExpertDashboardController extends Controller
{
    /**
     * Get expert dashboard metrics
     */
    public function metrics(Request $request)
    {
        $expertId = $request->user()->id;

        // Active mentees (unique students who had or have a session)
        $activeMentees = MentorSession::where('expert_id', $expertId)
            ->whereIn('status', ['completed', 'scheduled'])
            ->distinct('student_id')
            ->count('student_id');

        // Hours mentored (total duration of completed sessions in minutes / 60)
        $totalMinutes = MentorSession::where('expert_id', $expertId)
            ->where('status', 'completed')
            ->sum('duration_minutes');
        $hoursMentored = round($totalMinutes / 60, 1);

        // Pending Payout (example: $50 per hour)
        $pendingPayout = $hoursMentored * 50;

        // Average Rating (from expert reviews)
        $averageRating = \App\Models\ExpertReview::where('expert_id', $expertId)->avg('rating') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'active_mentees' => $activeMentees,
                'hours_mentored' => $hoursMentored,
                'pending_payout' => $pendingPayout,
                'average_rating' => round($averageRating, 1)
            ]
        ]);
    }

    /**
     * Get upcoming sessions for expert
     */
    public function upcomingSessions(Request $request)
    {
        $expertId = $request->user()->id;

        $sessions = MentorSession::with('student')
            ->where('expert_id', $expertId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'mentee' => $session->student->name ?? 'Mentee',
                    'time' => $session->scheduled_at->format('M d, g:i A'),
                    'topic' => $session->notes ?? 'Mentorship Session',
                    'meeting_link' => $session->meeting_link,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get earnings chart data
     */
    public function earningsChart(Request $request)
    {
        $earningsData = []; // TBD: Implement actual earnings calculation based on completed sessions

        return response()->json([
            'success' => true,
            'data' => $earningsData
        ]);
    }

    /**
     * Get mentee requests
     */
    public function menteeRequests(Request $request)
    {
        $expertId = $request->user()->id;

        $requests = MentorSession::with('student')
            ->where('expert_id', $expertId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'name' => $session->student->name ?? 'Student',
                    'reqType' => 'Requested a session for ' . $session->scheduled_at->format('M d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Get expert transactions/payout history
     */
    public function transactions(Request $request)
    {
        $transactions = []; // TBD: Implement actual transactions logic
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get all mentees
     */
    public function mentees(Request $request)
    {
        $expertId = $request->user()->id;

        // Fetch distinct mentees that have sessions with this expert
        $sessions = MentorSession::with('student')
            ->where('expert_id', $expertId)
            ->whereIn('status', ['completed', 'scheduled', 'pending'])
            ->get();
            
        $menteesMap = [];
        foreach ($sessions as $session) {
            $student = $session->student;
            if (!$student) continue;
            
            if (!isset($menteesMap[$student->id])) {
                $menteesMap[$student->id] = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'role' => $student->studentProfile->course ?? 'Software Engineer',
                    'company' => $student->studentProfile->college_name ?? 'BlueBoxx Student',
                    'status' => 'Active',
                    'progress' => 0, // TBD: Calculate actual progress
                    'rating' => '0.0', // TBD: Fetch actual rating
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($menteesMap)
        ]);
    }

    /**
     * Get all schedule / sessions (past and upcoming)
     */
    public function schedule(Request $request)
    {
        $expertId = $request->user()->id;

        $sessions = MentorSession::with('student')
            ->where('expert_id', $expertId)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $schedule = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'mentee' => $session->student ? $session->student->name : 'Unknown',
                'time' => $session->scheduled_at ? \Carbon\Carbon::parse($session->scheduled_at)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($session->scheduled_at)->addMinutes($session->duration_minutes)->format('h:i A') : 'TBD',
                'type' => 'Mentorship Session',
                'date' => $session->scheduled_at ? \Carbon\Carbon::parse($session->scheduled_at)->format('Y-m-d') : 'TBD',
                'booked' => true,
                'status' => $session->status
            ];
        });

        // Add a dummy available slot for UI purposes
        $schedule->push([
            'id' => 99999,
            'mentee' => null,
            'time' => '02:00 PM - 04:00 PM',
            'type' => 'Available Slot',
            'date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'booked' => false,
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    /**
     * Accept a mentee session request
     */
    public function acceptRequest(Request $request, $id)
    {
        $expertId = $request->user()->id;
        $session = MentorSession::where('expert_id', $expertId)->where('id', $id)->firstOrFail();
        $session->status = 'scheduled';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Mentee request accepted. Session scheduled.'
        ]);
    }

    public function declineRequest(Request $request, $id)
    {
        $expertId = $request->user()->id;
        $session = MentorSession::where('expert_id', $expertId)->where('id', $id)->firstOrFail();
        $session->status = 'cancelled';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Mentee request declined.'
        ]);
    }

    /**
     * Update meeting link for a session
     */
    public function updateMeetingLink(Request $request, $id)
    {
        $request->validate([
            'meeting_link' => 'required|url'
        ]);

        $expertId = $request->user()->id;
        $session = MentorSession::where('expert_id', $expertId)->where('id', $id)->firstOrFail();
        
        $session->meeting_link = $request->meeting_link;
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Meeting link updated successfully.',
            'data' => $session
        ]);
    }
}
