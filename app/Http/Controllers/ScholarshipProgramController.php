<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProgram;
use App\Models\ScholarshipApplication;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;
use App\Mail\ScholarshipApplicationMail;
use App\Jobs\SendQueuedEmailJob;

class ScholarshipProgramController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to view all active scholarships
     */
    public function index(Request $request)
    {
        $query = ScholarshipProgram::where('status', 'open');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'amount', 'deadline', 'created_at'],
            ['title', 'description']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    /**
     * Admin method to create a new scholarship program
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'deadline' => 'required|date|after:today',
            'status' => 'required|in:open,closed'
        ]);

        $scholarship = ScholarshipProgram::create($validated);

        // Send notifications to all students/users
        $students = User::role('student')->get();
        foreach ($students as $student) {
            $student->notify(new PlatformNotification(
                "New Scholarship Available! 🎓",
                "Apply for: '{$scholarship->title}' with a reward of {$scholarship->amount}.",
                'scholarship_published',
                ['scholarship_program_id' => $scholarship->id]
            ));
        }

        return response()->json([
            'message' => 'Scholarship program created successfully.',
            'data' => $scholarship
        ], 201);
    }


    /**
     * Public method to view a specific scholarship
     */
    public function show($id)
    {
        $scholarship = ScholarshipProgram::findOrFail($id);
        return response()->json($scholarship);
    }

    /**
     * Admin method to update a scholarship program
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'deadline' => 'sometimes|date',
            'status' => 'sometimes|in:open,closed'
        ]);

        $scholarship = ScholarshipProgram::findOrFail($id);
        $scholarship->update($validated);

        return response()->json([
            'message' => 'Scholarship program updated successfully.',
            'data' => $scholarship
        ]);
    }

    /**
     * Admin method to delete a scholarship program
     */
    public function destroy($id)
    {
        $scholarship = ScholarshipProgram::findOrFail($id);
        $scholarship->delete();

        return response()->json(['message' => 'Scholarship program deleted successfully.']);
    }

    /**
     * Auth method for students to apply
     */
    public function apply(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $scholarship = ScholarshipProgram::findOrFail($id);

        if ($scholarship->status !== 'open') {
            return response()->json(['message' => 'This scholarship is no longer open for applications.'], 400);
        }

        $existing = ScholarshipApplication::where('user_id', $request->user()->id)
            ->where('scholarship_program_id', $id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already applied for this scholarship.'], 400);
        }

        $application = ScholarshipApplication::create([
            'user_id' => $request->user()->id,
            'scholarship_program_id' => $id,
            'reason' => $request->reason,
            'status' => 'applied'
        ]);

        // Dispatch queued email confirmation
        SendQueuedEmailJob::dispatch(
            $request->user()->email,
            new ScholarshipApplicationMail($scholarship->title, now()->toDateString()),
            'Scholarship Application Received'
        );

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data' => $application
        ], 201);
    }
}

