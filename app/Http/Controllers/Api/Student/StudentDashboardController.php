<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseEnrollment;
use App\Models\InternshipApplication;
use App\Models\JobApplication;
use App\Models\IssuedCertificate;
use App\Models\MentorSession;
use App\Models\Job;
use App\Support\StorageHelper;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    /**
     * Get aggregate dashboard metrics.
     */
    public function metrics(Request $request)
    {
        $user = Auth::user();

        // 1. Courses stats
        $enrollments = CourseEnrollment::where('user_id', $user->id)->get();
        $activeCourses = $enrollments->where('status', 'active')->count();
        $completedCourses = $enrollments->where('status', 'completed')->count();

        // 2. Applications stats (Internships + Scholarships + Jobs)
        $internshipApps = InternshipApplication::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'selected', 'joined', 'completed'])
            ->count();
        
        $jobApps = JobApplication::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'hired'])
            ->count();
        
        $scholarshipApps = \App\Models\ScholarshipApplication::where('user_id', $user->id)
            ->whereNotIn('status', ['Rejected', 'Awarded'])
            ->count();

        $activeApplications = $internshipApps + $jobApps + $scholarshipApps;

        $interviews = InternshipApplication::where('user_id', $user->id)->whereIn('status', ['interview', 'shortlisted'])->count()
            + JobApplication::where('user_id', $user->id)->whereIn('status', ['interview', 'shortlisted'])->count()
            + \App\Models\ScholarshipApplication::where('user_id', $user->id)->where('status', 'shortlisted')->count();

        $offers = InternshipApplication::where('user_id', $user->id)->whereIn('status', ['selected', 'offer_sent', 'joined'])->count()
            + JobApplication::where('user_id', $user->id)->whereIn('status', ['hired', 'offer'])->count()
            + \App\Models\ScholarshipApplication::where('user_id', $user->id)->where('status', 'awarded')->count();

        // 3. Certificates stats
        $certificatesEarned = IssuedCertificate::where('user_id', $user->id)->count();

        // 4. Get active courses with progress
        $courses = CourseEnrollment::with('course', 'course.level', 'course.category')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->take(3)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->course?->id ?? 0,
                    'title' => $enrollment->course?->title ?? 'Course',
                    'level' => $enrollment->course?->level?->title ?? 'Beginner',
                    'category' => $enrollment->course?->category,
                    'thumbnail' => StorageHelper::url($enrollment->course?->thumbnail),
                    'progress' => $enrollment->progress_percentage ?? 0,
                ];
            });

        // 5. Get recent applications
        $recentInternships = InternshipApplication::with('internship', 'internship.company')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($app) {
                $title = $app->internship?->title ?? 'Internship Application';
                $companyName = $app->internship?->company_name ?? $app->internship?->company?->name ?? 'Blueboxx Partner';
                return [
                    'id' => 'int_' . $app->id,
                    'role' => $title,
                    'company' => $companyName,
                    'status' => ucfirst($app->status ?? 'Applied'),
                    'statusColor' => $this->getStatusColor(strtolower($app->status ?? 'applied')),
                    'appliedAt' => $app->created_at,
                    'appliedDate' => $app->created_at ? $app->created_at->diffForHumans() : 'Recently',
                ];
            });

        $recentScholarships = \App\Models\ScholarshipApplication::with('program')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => 'sch_' . $app->id,
                    'role' => $app->program?->title ?? 'Scholarship',
                    'company' => 'Blueboxx DA',
                    'status' => ucfirst($app->status ?? 'Applied'),
                    'statusColor' => $this->getStatusColor(strtolower($app->status ?? 'applied')),
                    'appliedAt' => $app->created_at,
                    'appliedDate' => $app->created_at ? $app->created_at->diffForHumans() : 'Recently',
                ];
            });

        $recentJobs = JobApplication::with('job')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => 'job_' . $app->id,
                    'role' => $app->job?->title ?? 'Job',
                    'company' => $app->job?->company_name ?? 'Unknown Company',
                    'status' => ucfirst($app->status ?? 'Applied'),
                    'statusColor' => $this->getStatusColor(strtolower($app->status ?? 'applied')),
                    'appliedAt' => $app->created_at,
                    'appliedDate' => $app->created_at ? $app->created_at->diffForHumans() : 'Recently',
                ];
            });

        $applications = collect($recentInternships)->concat($recentJobs)->concat($recentScholarships)
            ->sortByDesc('appliedAt')
            ->take(3)
            ->values();

        // 6. Upcoming classes / mentor sessions
        $upcomingSessions = MentorSession::with('expert.user')
            ->where('student_id', $user->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(2)
            ->get()
            ->map(function ($session) {
                return [
                    'title' => 'Mentor Session: ' . ($session->expert?->user?->first_name ?? 'Mentor'),
                    'course' => '1-on-1 Mentorship',
                    'date' => \Carbon\Carbon::parse($session->scheduled_at)->format('M d'),
                    'time' => \Carbon\Carbon::parse($session->scheduled_at)->format('g:i A'),
                    'join_url' => $session->meeting_link
                ];
            });

        return response()->json([
            'stats' => [
                'active_courses' => $activeCourses,
                'completed_courses' => $completedCourses,
                'active_applications' => $activeApplications,
                'interviews_scheduled' => $interviews,
                'offers_received' => $offers,
                'certificates_earned' => $certificatesEarned,
            ],
            'courses' => $courses,
            'applications' => $applications,
            'upcoming_classes' => $upcomingSessions
        ]);
    }

    /**
     * Get comprehensive placement progress (all applications + stats).
     */
    public function placementProgress(Request $request)
    {
        $user = Auth::user();

        $internships = InternshipApplication::with('internship', 'internship.company')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $scholarships = \App\Models\ScholarshipApplication::with('program')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $allApps = [];
        
        $interviews = 0;
        $offers = 0;

        foreach ($internships as $app) {
            if ($app->status === 'interview') $interviews++;
            if (in_array($app->status, ['selected', 'offer_sent', 'joined'])) $offers++;

            $companyName = $app->internship->company_name ?? 'Unknown Company';
            $companyLogo = $app->internship->company_logo;

            $allApps[] = [
                'id' => 'int_' . $app->id,
                'role' => $app->internship->title ?? 'Internship',
                'company' => $companyName,
                'logo' => $companyLogo
                            ? asset('storage/' . $companyLogo)
                            : "https://ui-avatars.com/api/?name=" . urlencode($companyName) . "&background=0d1635&color=fff",
                'appliedDate' => $app->created_at->format('M d, Y'),
                'status' => strtolower($app->status),
                'nextAction' => $app->status === 'interview' ? 'Interview Scheduled' : (in_array($app->status, ['selected', 'offer_sent', 'joined']) ? 'Offer Extended' : 'Application under review'),
            ];
        }

        foreach ($scholarships as $app) {
            if (strtolower($app->status) === 'shortlisted') $interviews++;
            if (strtolower($app->status) === 'awarded') $offers++;

            $allApps[] = [
                'id' => 'sch_' . $app->id,
                'role' => $app->program->title ?? 'Scholarship',
                'company' => 'Blueboxx DA',
                'logo' => "https://ui-avatars.com/api/?name=B&background=C9A227&color=fff",
                'appliedDate' => $app->created_at->format('M d, Y'),
                'status' => strtolower($app->status),
                'nextAction' => strtolower($app->status) === 'shortlisted' ? 'In Consideration' : (strtolower($app->status) === 'awarded' ? 'Scholarship Awarded' : 'Application under review'),
            ];
        }

        usort($allApps, function ($a, $b) {
            return strtotime($b['appliedDate']) - strtotime($a['appliedDate']);
        });

        $appliedCount = count($allApps);
        $shortlistedCount = collect($allApps)->where('status', 'shortlisted')->count();

        $score = min(100, 40 + ($appliedCount * 2) + ($interviews * 5) + ($offers * 10));

        return response()->json([
            'success' => true,
            'stats' => [
                'score' => $score,
                'total_applications' => $appliedCount,
                'interviews' => $interviews,
                'offers' => $offers,
            ],
            'pipeline' => [
                'applied' => $appliedCount,
                'shortlisted' => $shortlistedCount,
                'interview' => $interviews,
                'offered' => $offers,
            ],
            'applications' => $allApps
        ]);
    }

    private function getStatusColor($status)
    {
        return match ($status) {
            'pending' => 'bg-amber-50 text-amber-700',
            'reviewing' => 'bg-blue-50 text-blue-700',
            'interview' => 'bg-purple-50 text-purple-700',
            'hired' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
            default => 'bg-slate-50 text-slate-700',
        };
    }

    public function resume(Request $request)
    {
        $user = Auth::user();
        $doc = \Illuminate\Support\Facades\DB::table('student_documents')
            ->where('user_id', $user->id)
            ->where('type', 'resume')
            ->latest()
            ->first();
        
        return response()->json([
            'success' => true,
            'resume_url' => $doc && $doc->file_path ? asset('storage/' . $doc->file_path) : null
        ]);
    }

    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = Auth::user();

        if ($request->hasFile('resume')) {
            $path = $request->file('resume')->store('resumes/students', 'public');
            
            \Illuminate\Support\Facades\DB::table('student_documents')->updateOrInsert(
                ['user_id' => $user->id, 'type' => 'resume'],
                ['file_path' => $path, 'title' => 'Resume', 'created_at' => now(), 'updated_at' => now()]
            );

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully.',
                'resume_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to upload resume'], 400);
    }
}
