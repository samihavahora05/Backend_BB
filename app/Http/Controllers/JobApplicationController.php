<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\Job;
use App\Mail\JobApplicationMail;
use App\Jobs\SendQueuedEmailJob;

class JobApplicationController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = JobApplication::with(['job', 'jobseeker.jobSeekerProfile'])
            ->when($request->job_id, function($q, $job_id) {
                $q->where('job_id', $job_id);
            });

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['status', 'created_at'],
            ['status']
        );
            
        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'resume_path' => 'nullable|string',
            'cover_letter' => 'nullable|string'
        ]);

        $job = Job::with('company')->findOrFail($request->job_id);

        $application = JobApplication::create([
            'job_id' => $request->job_id,
            'jobseeker_id' => $request->user()->id,
            'resume_path' => $request->resume_path,
            'cover_letter' => $request->cover_letter,
            'status' => 'applied'
        ]);

        // 1. Notify user (DB & Push)
        $request->user()->notify(new PlatformNotification(
            "Application Submitted! 💼",
            "You have applied for the position: '{$job->title}' at {$job->company->name}.",
            'job_applied',
            ['job_id' => $job->id, 'application_id' => $application->id]
        ));

        // 2. Dispatch queued email confirmation
        SendQueuedEmailJob::dispatch(
            $request->user()->email,
            new JobApplicationMail($job->title, $job->company->name ?? 'Blueboxx Partner', now()->toDateString(), 'applied'),
            'Job Application Confirmation'
        );

        return response()->json($application, 201);
    }


    public function show($id)
    {
        return response()->json(JobApplication::with(['job', 'jobseeker'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);
        
        $request->validate(['status' => 'required|in:applied,shortlisted,rejected,hired']);
        
        $application->update(['status' => $request->status]);
        return response()->json($application);
    }

    public function destroy($id)
    {
        JobApplication::findOrFail($id)->delete();
        return response()->json(['message' => 'Application withdrawn']);
    }
}
