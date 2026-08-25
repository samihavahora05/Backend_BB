<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobInterview;

class CompanyDashboardController extends Controller
{
    /**
     * Get dashboard metrics and data for the company portal
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;

        $jobs = Job::where('company_id', $companyId)->latest()->get();
        $activeJobsCount = $jobs->whereIn('status', ['Active', 'active', 'open', 'published'])->count();
        $pendingJobsCount = $jobs->whereIn('status', ['Pending', 'pending', 'pending_approval', 'Pending Approval'])->count();

        $jobIds = $jobs->pluck('id');
        $applications = JobApplication::whereIn('job_id', $jobIds)->get();
        $totalApplicants = $applications->count();
        $hiredCount = $applications->whereIn('status', ['offer_sent', 'accepted', 'joined', 'hired'])->count();

        $activeJobsList = $jobs->map(function($job) {
            $statusNormalized = strtolower($job->status ?? 'pending_approval');
            $displayStatus = 'Pending Approval';
            if (in_array($statusNormalized, ['active', 'open', 'published'])) {
                $displayStatus = 'Active';
            } elseif (in_array($statusNormalized, ['draft'])) {
                $displayStatus = 'Draft';
            } elseif (in_array($statusNormalized, ['rejected'])) {
                $displayStatus = 'Rejected';
            } elseif (in_array($statusNormalized, ['closed', 'expired'])) {
                $displayStatus = 'Closed';
            }

            return [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->employment_type ?? 'Full-Time',
                'status' => $displayStatus,
                'raw_status' => $job->status,
                'type' => $job->remote_type ?? ($job->location === 'Remote' ? 'Remote' : 'On-site'),
                'applicants' => $job->applications()->count(),
                'created_at' => $job->created_at ? $job->created_at->diffForHumans() : 'Recently',
            ];
        })->take(6)->values();

        $today = \Carbon\Carbon::today();
        $applicationIds = $applications->pluck('id');
        $interviews = JobInterview::whereIn('application_id', $applicationIds)
            ->whereDate('scheduled_at', '>=', $today)
            ->with(['application.user', 'job'])
            ->latest('scheduled_at')
            ->take(5)
            ->get()
            ->map(function($interview) {
                return [
                    'id' => $interview->id,
                    'name' => $interview->application && $interview->application->user ? $interview->application->user->name : 'Candidate',
                    'role' => $interview->job ? $interview->job->title : 'Applicant',
                    'date' => \Carbon\Carbon::parse($interview->scheduled_at)->isToday() ? 'Today' : \Carbon\Carbon::parse($interview->scheduled_at)->format('M d'),
                    'time' => \Carbon\Carbon::parse($interview->scheduled_at)->format('h:i A'),
                    'type' => $interview->type ?? 'Interview Round',
                    'match' => 'High'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'active_jobs' => $activeJobsCount,
                    'total_applicants' => $totalApplicants,
                    'pending_jobs' => $pendingJobsCount,
                    'hired' => $hiredCount,
                ],
                'active_jobs_list' => $activeJobsList,
                'today_interviews' => $interviews,
            ]
        ]);
    }

    /**
     * Get detailed analytics for the company
     */
    public function analytics(Request $request)
    {
        $companyId = $request->user()->id;

        $jobs = Job::where('company_id', $companyId)->get();
        $jobIds = $jobs->pluck('id');

        $applications = JobApplication::whereIn('job_id', $jobIds)
            ->with('user')
            ->get();

        $applicationIds = $applications->pluck('id');
        $interviews = JobInterview::whereIn('application_id', $applicationIds)->get();

        $activeJobsCount = $jobs->whereIn('status', ['Active', 'active', 'open'])->count();
        $pendingJobsCount = $jobs->whereIn('status', ['Pending', 'pending', 'pending_approval'])->count();
        $closedJobsCount = $jobs->whereIn('status', ['Closed', 'closed'])->count();

        $totalApplicants = $applications->count();
        $inReview = $applications->whereIn('status', ['under_review', 'shortlisted'])->count();
        $inInterview = $applications->where('status', 'interview_scheduled')->count();
        $offers = $applications->whereIn('status', ['offer_sent', 'accepted', 'joined', 'hired'])->count();
        $rejected = $applications->where('status', 'rejected')->count();
        
        $applied = $applications->where('status', 'applied')->count();

        $conversionRate = $totalApplicants > 0 ? round(($offers / $totalApplicants) * 100, 1) : 0;
        $interviewRate = $totalApplicants > 0 ? round(($inInterview / $totalApplicants) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'active_jobs' => $activeJobsCount,
                    'total_applicants' => $totalApplicants,
                    'pending_jobs' => $pendingJobsCount,
                    'hired' => $offers,
                    'conversion_rate' => $conversionRate,
                    'interview_rate' => $interviewRate
                ],
                'pipeline' => [
                    'applied' => $applied,
                    'in_review' => $inReview,
                    'interview' => $inInterview,
                    'offer' => $offers,
                    'rejected' => $rejected
                ]
            ]
        ]);
    }
}
