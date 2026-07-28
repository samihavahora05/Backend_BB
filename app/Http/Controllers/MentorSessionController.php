<?php

namespace App\Http\Controllers;

use App\Models\MentorSession;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;
use App\Mail\MentorSessionMail;
use App\Jobs\SendQueuedEmailJob;

class MentorSessionController extends Controller
{
    use PaginateQuery;

    /**
     * Get user's mentor sessions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = MentorSession::where('student_id', $user->id)
            ->orWhere('expert_id', $user->id)
            ->with(['student', 'expert.expertProfile']);

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['scheduled_at', 'created_at'],
            ['notes']
        );
            
        return response()->json(array_merge(['success' => true], $paginated));
    }

    /**
     * Book a new session
     */
    public function store(Request $request)
    {
        $request->validate([
            'expert_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:120',
            'meeting_url' => 'nullable|url',
            'notes' => 'nullable|string|max:1000'
        ]);

        $expert = User::findOrFail($request->expert_id);
        $student = $request->user();

        $session = MentorSession::create([
            'student_id' => $student->id,
            'expert_id' => $expert->id,
            'scheduled_at' => $request->scheduled_at,
            'duration_minutes' => $request->duration_minutes ?? 45,
            'meeting_url' => $request->meeting_url ?? 'https://zoom.us/j/' . rand(100000000, 999999999),
            'notes' => $request->notes,
            'status' => 'scheduled'
        ]);

        // 1. Notify Student
        $student->notify(new PlatformNotification(
            "Mentor Session Booked! 📅",
            "Your session with Mentor {$expert->name} has been scheduled for {$session->scheduled_at}.",
            'mentor_session_booked',
            ['session_id' => $session->id, 'expert_id' => $expert->id]
        ));

        // 2. Notify Expert (Mentor)
        $expert->notify(new PlatformNotification(
            "New Session Booked! 📅",
            "Student {$student->name} has booked a session with you for {$session->scheduled_at}.",
            'mentor_session_booked',
            ['session_id' => $session->id, 'student_id' => $student->id]
        ));

        // 3. Email Student
        SendQueuedEmailJob::dispatch(
            $student->email,
            new MentorSessionMail($expert->name, $session->notes ?? 'Mentorship Session', $session->scheduled_at),
            'Mentor Session Booking Confirmed'
        );

        // 4. Email Expert
        SendQueuedEmailJob::dispatch(
            $expert->email,
            new MentorSessionMail($student->name, $session->notes ?? 'Mentorship Session', $session->scheduled_at),
            'New Mentor Session Booked'
        );

        return response()->json($session, 201);
    }

}
