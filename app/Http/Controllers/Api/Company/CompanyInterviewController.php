<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobInterview;
use Carbon\Carbon;
use App\Notifications\PlatformNotification;

class CompanyInterviewController extends Controller
{
    /**
     * Get all scheduled interviews for the company
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        $applicationIds = JobApplication::whereIn('job_id', $jobIds)->pluck('id');
        
        $interviews = JobInterview::whereIn('application_id', $applicationIds)
            ->with(['application.user', 'application.job'])
            ->latest('scheduled_at')
            ->get()
            ->map(function($interview) {
                return [
                    'id' => $interview->id,
                    'applicationId' => $interview->application_id,
                    'name' => $interview->application && $interview->application->user ? $interview->application->user->name : 'Unknown',
                    'role' => $interview->application && $interview->application->job ? $interview->application->job->title : 'Unknown Role',
                    'date' => $interview->scheduled_at ? Carbon::parse($interview->scheduled_at)->format('M d, Y') : 'TBD',
                    'time' => $interview->scheduled_at ? Carbon::parse($interview->scheduled_at)->format('h:i A') : 'TBD',
                    'mode' => $interview->mode,
                    'meetingLink' => $interview->meeting_link,
                    'location' => $interview->location,
                    'round' => 'Round ' . $interview->round_number,
                    'recommendation' => $interview->recommendation,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $interviews
        ]);
    }

    /**
     * Schedule a new interview
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:job_applications,id',
            'date' => 'required|date',
            'time' => 'required|string',
            'mode' => 'required|string|in:google_meet,zoom,offline',
            'meeting_link' => 'nullable|string',
            'location' => 'nullable|string',
            'round_number' => 'nullable|integer'
        ]);

        $companyId = $request->user()->id;
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        
        // Verify application belongs to this company
        $application = JobApplication::whereIn('job_id', $jobIds)->findOrFail($validated['application_id']);

        $interview = new JobInterview();
        $interview->application_id = $application->id;
        $interview->interviewer_id = $companyId;
        $interview->round_number = $validated['round_number'] ?? 1;
        $interview->mode = $validated['mode'];
        $interview->meeting_link = $validated['meeting_link'] ?? null;
        $interview->location = $validated['location'] ?? null;
        $interview->scheduled_at = Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $interview->recommendation = 'pending';
        
        $interview->save();
        
        // Update application status automatically
        $application->status = 'interview_scheduled';
        $application->save();
        
        $dateStr = Carbon::parse($validated['date'])->format('M d, Y');
        $application->user->notify(new PlatformNotification(
            'Interview Scheduled',
            "An interview has been scheduled for {$application->job->title} on {$dateStr} at {$validated['time']}.",
            'interview_scheduled',
            ['interview_id' => $interview->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Interview scheduled successfully.',
            'data' => $interview
        ], 201);
    }

    /**
     * Update an interview (Marks/Recommendation)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'marks_obtained' => 'nullable|integer',
            'max_marks' => 'nullable|integer',
            'feedback' => 'nullable|string',
            'recommendation' => 'required|string|in:hire,hold,reject,pending'
        ]);
        
        $companyId = $request->user()->id;
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        $applicationIds = JobApplication::whereIn('job_id', $jobIds)->pluck('id');

        $interview = JobInterview::whereIn('application_id', $applicationIds)->findOrFail($id);
        
        $interview->fill($validated);
        $interview->save();
        
        // If rejected, reject application
        if ($validated['recommendation'] === 'reject') {
            $application = $interview->application;
            $application->status = 'rejected';
            $application->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Interview feedback saved.',
            'data' => $interview
        ]);
    }
}
