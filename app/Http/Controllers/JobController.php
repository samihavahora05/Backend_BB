<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;

class JobController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = Job::with('company')
            ->where('status', 'open');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'location', 'created_at'],
            ['title', 'description', 'location']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'job_type' => 'required|string',
            'location' => 'required|string',
            'requirements' => 'nullable|array'
        ]);

        $job = Job::create([
            'company_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'job_type' => $request->job_type,
            'location' => $request->location,
            'salary_range' => $request->salary_range,
            'requirements' => $request->requirements,
            'status' => $request->status ?? 'open',
        ]);

        // Send notifications to all students/job-seekers
        $students = User::role(['student', 'job-seeker'])->get();
        foreach ($students as $student) {
            $student->notify(new PlatformNotification(
                "New Job Posted! 💼",
                "New position available: '{$job->title}' at " . $request->user()->name,
                'job_posted',
                ['job_id' => $job->id]
            ));
        }

        return response()->json($job, 201);
    }


    public function show($id)
    {
        return response()->json(Job::with('company')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $job->update($request->all());
        return response()->json($job);
    }

    public function destroy($id)
    {
        Job::findOrFail($id)->delete();
        return response()->json(['message' => 'Job deleted']);
    }
}
