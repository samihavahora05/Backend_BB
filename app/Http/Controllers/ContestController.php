<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestRegistration;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;
use App\Mail\ContestRegistrationMail;
use App\Jobs\SendQueuedEmailJob;

class ContestController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to view active/upcoming contests
     */
    public function index(Request $request)
    {
        $query = Contest::whereIn('status', ['upcoming', 'ongoing']);

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'start_date', 'created_at'],
            ['title', 'description']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    /**
     * Admin method to list every contest, regardless of status,
     * with registration counts for the manager UI.
     */
    public function adminIndex(Request $request)
    {
        $query = Contest::withCount('registrations')->with(['category:id,name', 'college:id,name']);

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'start_date', 'status', 'created_at'],
            ['title', 'description']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    /**
     * Admin method to create a new contest
     */
    public function store(Request $request)
    {
        if ($request->has('status')) {
            $s = strtolower($request->status);
            if ($s === 'active') $s = 'ongoing';
            $request->merge(['status' => $s]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:upcoming,ongoing,completed',
            'category_id' => 'nullable',
            'college_id' => 'nullable',
        ]);

        $contest = Contest::create($validated);

        // Send notifications to all students/users
        try {
            $students = User::role('student')->get();
            foreach ($students as $student) {
                $student->notify(new PlatformNotification(
                    "New Contest Published! 🏆",
                    "Join the contest: '{$contest->title}' scheduled on {$contest->start_date}.",
                    'contest_published',
                    ['contest_id' => $contest->id]
                ));
            }
        } catch (\Throwable $e) {
            // Ignore notification error if roles not configured
        }

        return response()->json([
            'success' => true,
            'message' => 'Contest created successfully.',
            'data' => $contest
        ], 201);
    }


    /**
     * Public method to view a specific contest
     */
    public function show($id)
    {
        $contest = Contest::findOrFail($id);
        return response()->json(['success' => true, 'data' => $contest]);
    }

    /**
     * Admin method to update a contest
     */
    public function update(Request $request, $id)
    {
        if ($request->has('status')) {
            $s = strtolower($request->status);
            if ($s === 'active') $s = 'ongoing';
            $request->merge(['status' => $s]);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'sometimes|in:upcoming,ongoing,completed',
            'category_id' => 'nullable',
            'college_id' => 'nullable',
        ]);

        $contest = Contest::findOrFail($id);
        $contest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contest updated successfully.',
            'data' => $contest
        ]);
    }

    /**
     * Admin method to delete a contest
     */
    public function destroy($id)
    {
        $contest = Contest::findOrFail($id);
        $contest->delete();

        return response()->json(['message' => 'Contest deleted successfully.']);
    }

    /**
     * Auth method for students to register
     */
    public function registerUser(Request $request, $id)
    {
        $contest = Contest::findOrFail($id);

        if ($contest->status === 'completed') {
            return response()->json(['message' => 'This contest has already ended.'], 400);
        }

        $existing = ContestRegistration::where('user_id', $request->user()->id)
            ->where('contest_id', $id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You are already registered for this contest.'], 400);
        }

        $registration = ContestRegistration::create([
            'user_id' => $request->user()->id,
            'contest_id' => $id,
            'status' => 'registered'
        ]);

        // Dispatch queued email confirmation
        try {
            SendQueuedEmailJob::dispatch(
                $request->user()->email,
                new ContestRegistrationMail($contest->title, $contest->start_date),
                'Contest Registration Confirmed'
            );
        } catch (\Throwable $e) {
            \Log::info("Contest email notification skipped: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Registered for contest successfully.',
            'data' => $registration
        ], 201);
    }

    public function adminRegistrations(\Illuminate\Http\Request $request)
    {
        $registrations = ContestRegistration::with(['user', 'contest'])
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id'           => (string)$r->id,
                    'studentName'  => $r->user?->name ?? 'Participant',
                    'studentEmail' => $r->user?->email ?? '',
                    'phone'        => $r->phone ?? ($r->user?->phone ?? 'N/A'),
                    'college'      => $r->college_name ?? 'N/A',
                    'domainTrack'  => $r->domain_track ?? 'N/A',
                    'teamName'     => $r->team_name ?? 'Solo',
                    'teamMembers'  => $r->team_members ?? '',
                    'contestTitle' => $r->contest?->title ?? 'Contest',
                    'appliedDate'  => $r->created_at ? $r->created_at->format('Y-m-d H:i') : 'N/A',
                    'status'       => ucfirst($r->status ?? 'registered'),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $registrations
        ]);
    }

    public function adminSubmissions(\Illuminate\Http\Request $request)
    {
        $submissions = \App\Models\ContestSubmission::with(['registration.user', 'registration.contest'])
            ->latest()
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => (string)$s->id,
                    'studentName'  => $s->registration?->user?->name ?? 'Participant',
                    'taskTitle'    => $s->project_title ?? 'Project Submission',
                    'contestTitle' => $s->registration?->contest?->title ?? 'Contest',
                    'submittedAt'  => $s->created_at ? $s->created_at->format('Y-m-d H:i') : 'N/A',
                    'status'       => $s->score !== null ? 'Graded' : 'Pending Review',
                    'files'        => $s->repo_url ? [$s->repo_url] : [],
                    'link'         => $s->demo_url ?? ($s->repo_url ?? ''),
                    'score'        => $s->score,
                    'totalMarks'   => 100,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $submissions
        ]);
    }
}
