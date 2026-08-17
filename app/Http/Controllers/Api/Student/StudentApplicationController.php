<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\InternshipApplication;
use App\Models\ScholarshipApplication;
use App\Models\ContestRegistration;
use App\Models\MentorSession;
use App\Models\CourseEnrollment;

class StudentApplicationController extends Controller
{
    /**
     * Get all applications for the unified My Applications dashboard.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $applications = collect();

        // 1. Job Applications
        $jobs = JobApplication::with('job:id,title,company_id')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => 'job_'.$app->id,
                    'type' => 'Job',
                    'title' => $app->job->title ?? 'N/A',
                    'status' => $app->status,
                    'applied_on' => $app->created_at,
                    'link' => '/student/jobs'
                ];
            });
        $applications = $applications->concat($jobs);

        // 2. Internship Applications
        $internships = InternshipApplication::with('internship:id,title')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => 'internship_'.$app->id,
                    'type' => 'Internship',
                    'title' => $app->internship->title ?? 'N/A',
                    'status' => $app->status,
                    'applied_on' => $app->created_at,
                    'link' => '/student/internships'
                ];
            });
        $applications = $applications->concat($internships);

        // 3. Scholarship Applications
        $scholarships = ScholarshipApplication::with('program:id,name')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => 'scholarship_'.$app->id,
                    'type' => 'Scholarship',
                    'title' => $app->program->name ?? 'N/A',
                    'status' => $app->status,
                    'applied_on' => $app->created_at,
                    'link' => '/student/scholarships'
                ];
            });
        $applications = $applications->concat($scholarships);

        // Contests are managed separately on /student/contests


        // Sort by applied_on desc
        $sortedApplications = $applications->sortByDesc('applied_on')->values()->map(function ($app) {
            $app['applied_on'] = $app['applied_on']->format('M d, Y');
            return $app;
        });

        return response()->json([
            'success' => true,
            'data' => $sortedApplications
        ]);
    }
}
