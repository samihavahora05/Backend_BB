<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication;
use App\Notifications\PlatformNotification;

class CompanyApplicantController extends Controller
{
    /**
     * Get all applicants for the company's jobs
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        
        $applications = JobApplication::whereIn('job_id', $jobIds)
            ->with(['job', 'user.jobSeekerProfile', 'user.studentProfile'])
            ->latest()
            ->get()
            ->map(function($app) {
                return [
                    'id' => $app->id,
                    'jobId' => $app->job_id,
                    'jobTitle' => $app->job ? $app->job->title : 'Unknown Job',
                    'applicantName' => $app->user ? $app->user->name : 'Unknown',
                    'email' => $app->user ? $app->user->email : '',
                    'phone' => $app->user ? ($app->user->jobSeekerProfile->phone ?? $app->user->studentProfile->phone ?? $app->user->phone ?? '') : '',
                    'status' => $app->status,
                    'appliedAt' => $app->created_at->diffForHumans(),
                    'appliedDate' => $app->created_at->format('M d, Y'),
                    'resumeUrl' => $app->resume_path ? asset('storage/' . $app->resume_path) : null,
                    'portfolioUrl' => $app->portfolio_url,
                    'githubUrl' => $app->github_url,
                    'linkedinUrl' => $app->linkedin_url,
                    'score' => rand(70, 95) // Placeholder AI score
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * Show a single applicant's details
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $jobIds = Job::where('company_id', $companyId)->pluck('id');

        $application = JobApplication::whereIn('job_id', $jobIds)
            ->with(['job', 'user.jobSeekerProfile', 'user.studentProfile', 'user.education', 'user.experience'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $application->id,
                'job' => $application->job,
                'user' => $application->user,
                'status' => $application->status,
                'coverLetter' => $application->cover_letter,
                'resumeUrl' => $application->resume_path ? asset('storage/' . $application->resume_path) : null,
                'portfolioUrl' => $application->portfolio_url,
                'githubUrl' => $application->github_url,
                'linkedinUrl' => $application->linkedin_url,
                'customAnswers' => json_decode($application->custom_answers, true),
                'appliedAt' => $application->created_at->format('M d, Y H:i A')
            ]
        ]);
    }

    /**
     * Update an applicant's status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:applied,under_review,shortlisted,interview_scheduled,rejected,offer_sent,accepted,joined,completed'
        ]);
        
        $companyId = $request->user()->id;
        $jobIds = Job::where('company_id', $companyId)->pluck('id');

        $application = JobApplication::whereIn('job_id', $jobIds)->findOrFail($id);
        
        $application->status = $validated['status'];
        $application->save();
        
        // Log activity if needed or create Shortlist record if shortlisted.
        if ($validated['status'] === 'shortlisted') {
            \App\Models\JobShortlist::firstOrCreate([
                'job_id' => $application->job_id,
                'user_id' => $application->user_id
            ]);
            
            $application->user->notify(new PlatformNotification(
                'Application Shortlisted',
                "Your application for {$application->job->title} has been shortlisted!",
                'application_update'
            ));
        } elseif ($validated['status'] === 'rejected') {
            $application->user->notify(new PlatformNotification(
                'Application Update',
                "Your application for {$application->job->title} has been rejected.",
                'application_update'
            ));
        } elseif ($validated['status'] === 'interview_scheduled') {
            \App\Models\JobInterview::firstOrCreate(
                ['application_id' => $application->id],
                [
                    'round_number' => 1,
                    'mode' => 'google_meet',
                    'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
                    'recommendation' => 'pending'
                ]
            );
            $application->user->notify(new \App\Notifications\PlatformNotification(
                'Interview Scheduled',
                "An interview has been scheduled for your application to {$application->job->title}.",
                'application_update'
            ));
        } elseif ($validated['status'] === 'offer_sent') {
            \App\Models\JobOffer::firstOrCreate(
                ['application_id' => $application->id],
                [
                    'status' => 'pending',
                    'valid_until' => now()->addDays(7)
                ]
            );
            
            $application->user->notify(new \App\Notifications\PlatformNotification(
                'Offer Received!',
                "You have received an offer for {$application->job->title}!",
                'offer'
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Applicant status updated successfully.',
            'data' => $application
        ]);
    }
}
