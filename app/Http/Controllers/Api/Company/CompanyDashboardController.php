<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyDashboardController extends Controller
{
    /**
     * Get dashboard metrics and data for the company portal
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;

        $jobs = \App\Models\Job::where('company_id', $companyId)->latest()->get();
        $activeJobsCount = $jobs->whereIn('status', ['Active', 'active'])->count();
        $pendingJobsCount = $jobs->whereIn('status', ['Pending', 'pending'])->count();

        $jobIds = $jobs->pluck('id');
        $applications = \App\Models\JobApplication::whereIn('job_id', $jobIds)->get();
        $totalApplicants = $applications->count();
        $hiredCount = $applications->whereIn('status', ['offer_sent', 'accepted', 'joined'])->count();

        $activeJobsList = $jobs->whereIn('status', ['Active', 'active'])->take(5)->map(function($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->job_type,
                'status' => $job->status,
                'type' => $job->location === 'Remote' ? 'Remote' : 'On-site',
                'applicants' => $job->applications()->count()
            ];
        })->values();

        $today = \Carbon\Carbon::today();
        $applicationIds = $applications->pluck('id');
        $interviews = \App\Models\JobInterview::whereIn('application_id', $applicationIds)
            ->whereDate('scheduled_at', $today)
            ->with(['application.user', 'job'])
            ->get()
            ->map(function($interview) {
                return [
                    'id' => $interview->id,
                    'name' => $interview->application && $interview->application->user ? $interview->application->user->name : 'Unknown Candidate',
                    'role' => $interview->job ? $interview->job->title : 'Unknown Role',
                    'date' => 'Today',
                    'time' => \Carbon\Carbon::parse($interview->scheduled_at)->format('h:i A'),
                    'type' => $interview->type ?? 'Technical Round',
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

        $jobs = \App\Models\Job::where('company_id', $companyId)->get();
        $jobIds = $jobs->pluck('id');

        $applications = \App\Models\JobApplication::whereIn('job_id', $jobIds)
            ->with('user')
            ->get();

        $applicationIds = $applications->pluck('id');
        $interviews = \App\Models\JobInterview::whereIn('application_id', $applicationIds)->get();

        $activeJobsCount = $jobs->whereIn('status', ['Active', 'active'])->count();
        $pendingJobsCount = $jobs->whereIn('status', ['Pending', 'pending'])->count();
        $closedJobsCount = $jobs->whereIn('status', ['Closed', 'closed'])->count();

        $totalApplicants = $applications->count();
        $inReview = $applications->whereIn('status', ['under_review', 'shortlisted'])->count();
        $inInterview = $applications->where('status', 'interview_scheduled')->count();
        $offers = $applications->whereIn('status', ['offer_sent', 'accepted', 'joined'])->count();
        $rejected = $applications->where('status', 'rejected')->count();
        
        $applied = $applications->where('status', 'applied')->count();

        $conversionRate = $totalApplicants > 0 ? round(($offers / $totalApplicants) * 100, 1) : 0;
        $interviewRate = $totalApplicants > 0 ? round(($inInterview / $totalApplicants) * 100, 1) : 0;
        $avgMatch = 85; // Placeholder

        $topApplicants = $applications->sortByDesc('id')->take(5)->map(function($app) {
            return [
                'id' => $app->id,
                'name' => $app->user ? $app->user->name : 'Unknown',
                'role' => 'Job Application',
                'match' => rand(70, 95)
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => [
                    'active' => $activeJobsCount,
                    'pending' => $pendingJobsCount,
                    'closed' => $closedJobsCount,
                    'recent' => $jobs->sortByDesc('created_at')->take(6)->map(function($j) {
                        return ['id' => $j->id, 'title' => $j->title, 'category' => $j->job_type, 'status' => $j->status, 'applicants' => 0];
                    })->values()
                ],
                'applicants' => [
                    'total' => $totalApplicants,
                    'pipeline' => [
                        ['stage' => 'Applied', 'count' => $applied, 'color' => 'bg-slate-400'],
                        ['stage' => 'In Review', 'count' => $inReview, 'color' => 'bg-amber-400'],
                        ['stage' => 'Interview', 'count' => $inInterview, 'color' => 'bg-blue-500'],
                        ['stage' => 'Offer', 'count' => $offers, 'color' => 'bg-emerald-500'],
                        ['stage' => 'Rejected', 'count' => $rejected, 'color' => 'bg-red-400'],
                    ],
                    'rates' => [
                        'conversion' => $conversionRate,
                        'interview' => $interviewRate,
                        'avg_match' => $avgMatch
                    ],
                    'top' => $topApplicants
                ],
                'interviews' => [
                    'upcoming' => $interviews->where('status', 'Scheduled')->count(),
                    'completed' => $interviews->where('status', 'Completed')->count(),
                ]
            ]
        ]);
    }
}
