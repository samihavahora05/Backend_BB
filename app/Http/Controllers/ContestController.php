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
}

