<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Certificate;
use App\Models\StudentProgress;
use App\Models\MentorSession;
use App\Models\ExpertProfile;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    /**
     * Overall summary KPIs (8 cards at top of analytics page)
     */
    public function summary()
    {
        $students    = User::whereDoesntHave('roles')->count()
            ?: User::count();
        $instructors = ExpertProfile::count();
        $colleges    = User::whereHas('roles', fn($q) => $q->whereIn('name', ['college', 'institution']))->count();

        $activeInternships = Internship::whereIn('status', ['active', 'open'])->count();
        $activeJobs        = Job::where('status', 'active')->count();
        $totalCourses      = Course::count();
        $certificates      = Certificate::count();
        $applications      = JobApplication::count() + InternshipApplication::count();

        return response()->json([
            'data' => compact(
                'students', 'instructors', 'colleges',
                'activeInternships', 'activeJobs', 'totalCourses',
                'certificates', 'applications'
            )
        ]);
    }

    /**
     * Tab-specific sub-KPIs for the analytics tabs
     */
    public function tabStats(Request $request)
    {
        $tab = $request->input('tab', 'student');

        $data = match ($tab) {
            'student' => $this->studentStats(),
            'instructor' => $this->instructorStats(),
            'institution' => $this->institutionStats(),
            'internship' => $this->internshipStats(),
            'placement' => $this->placementStats(),
            default => [],
        };

        return response()->json(['data' => $data]);
    }

    /**
     * Monthly chart data (configurable by tab + metric)
     */
    public function chartData(Request $request)
    {
        $tab    = $request->input('tab', 'student');
        $metric = $request->input('metric', 'registrations');

        $data = match ("{$tab}.{$metric}") {
            'student.registrations'  => $this->monthlyEnrollments(),
            'student.completions'    => $this->monthlyCompletions(),
            'internship.applications'=> $this->monthlyInternshipApps(),
            'placement.placements'   => $this->monthlyPlacements(),
            default                  => array_fill(0, 12, 0),
        };

        return response()->json(['data' => $data]);
    }

    /**
     * Leaderboards: top students + top colleges
     */
    public function leaderboards()
    {
        // Top students by enrollment count
        $topStudents = User::withCount('courseEnrollments')
            ->having('course_enrollments_count', '>', 0)
            ->orderByDesc('course_enrollments_count')
            ->take(5)
            ->get()
            ->map(fn($u, $i) => [
                'name'        => trim($u->first_name . ' ' . $u->last_name),
                'sub'         => $u->course_enrollments_count . ' courses enrolled',
                'value'       => $u->course_enrollments_count . ' courses',
                'badge'       => $i === 0 ? '🏆' : ($i === 1 ? '🥈' : ''),
                'avatarColor' => ['bg-blue-600','bg-purple-600','bg-emerald-600','bg-amber-500','bg-rose-500'][$i] ?? 'bg-slate-400',
            ]);

        if ($topStudents->isEmpty()) {
            $topStudents = collect([
                ['name' => 'No students yet', 'sub' => 'Enroll students to see leaders', 'value' => '—', 'badge' => '', 'avatarColor' => 'bg-slate-300'],
            ]);
        }

        // Top colleges (users with college role)
        $topColleges = User::whereHas('roles', fn($q) => $q->whereIn('name', ['college', 'institution']))
            ->withCount('courseEnrollments')
            ->orderByDesc('course_enrollments_count')
            ->take(5)
            ->get()
            ->map(fn($c, $i) => [
                'name'        => trim($c->first_name . ' ' . $c->last_name) ?: $c->email,
                'sub'         => $c->course_enrollments_count . ' student enrollments',
                'value'       => $c->course_enrollments_count,
                'badge'       => $i === 0 ? '🏆' : '',
                'avatarColor' => ['bg-blue-600','bg-purple-600','bg-emerald-600','bg-amber-500','bg-rose-500'][$i] ?? 'bg-slate-400',
            ]);

        if ($topColleges->isEmpty()) {
            $topColleges = collect([
                ['name' => 'No colleges registered', 'sub' => 'Colleges will appear here once registered', 'value' => '—', 'badge' => '', 'avatarColor' => 'bg-slate-300'],
            ]);
        }

        return response()->json([
            'data' => [
                'top_students' => $topStudents,
                'top_colleges' => $topColleges,
            ]
        ]);
    }

    /**
     * Recent activity feed from admin_logs
     */
    public function recentActivity()
    {
        $logs = \App\Models\AdminLog::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($l) => [
                'text'  => $l->action,
                'time'  => $l->created_at->diffForHumans(),
                'user'  => $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : 'System',
            ]);

        // Fallback: enrollment activity
        if ($logs->isEmpty()) {
            $logs = CourseEnrollment::with(['user', 'course'])
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($e) => [
                    'text' => trim(($e->user->first_name ?? '') . ' ' . ($e->user->last_name ?? '')) . ' enrolled in ' . ($e->course->title ?? 'a course'),
                    'time' => $e->created_at->diffForHumans(),
                    'user' => trim(($e->user->first_name ?? '') . ' ' . ($e->user->last_name ?? '')),
                ]);
        }

        return response()->json(['data' => $logs]);
    }

    // ──────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────

    private function studentStats(): array
    {
        $total       = User::count();
        $enrollments = CourseEnrollment::count();
        $completed   = CourseEnrollment::where('status', 'completed')->count();
        $avgScore    = round(StudentProgress::avg('average_quiz_score') ?? 0, 1);
        $certs       = Certificate::count();

        return [
            ['label' => 'Total Students',      'value' => $total,         'color' => '#3B82F6'],
            ['label' => 'Total Enrollments',   'value' => $enrollments,   'color' => '#10B981'],
            ['label' => 'Courses Completed',   'value' => $completed,     'color' => '#F59E0B'],
            ['label' => 'Avg Quiz Score',      'value' => $avgScore . '%','color' => '#EC4899'],
            ['label' => 'Certificates Earned', 'value' => $certs,         'color' => '#6366F1'],
        ];
    }

    private function instructorStats(): array
    {
        $total    = ExpertProfile::count();
        $courses  = Course::count();
        $students = CourseEnrollment::distinct('user_id')->count('user_id');
        $sessions = MentorSession::count();

        return [
            ['label' => 'Total Instructors',    'value' => $total,    'color' => '#8B5CF6'],
            ['label' => 'Active Courses',       'value' => $courses,  'color' => '#3B82F6'],
            ['label' => 'Students Taught',      'value' => $students, 'color' => '#10B981'],
            ['label' => 'Mentorship Sessions',  'value' => $sessions, 'color' => '#F59E0B'],
        ];
    }

    private function institutionStats(): array
    {
        $colleges  = User::whereHas('roles', fn($q) => $q->whereIn('name', ['college', 'institution']))->count();
        $students  = User::count();
        $internship= InternshipApplication::where('status', 'completed')->count();
        $placements= JobApplication::whereIn('status', ['offer_released', 'selected'])->count();

        return [
            ['label' => 'Registered Colleges',     'value' => $colleges,   'color' => '#0EA5E9'],
            ['label' => 'Total Students',           'value' => $students,   'color' => '#3B82F6'],
            ['label' => 'Internships Completed',    'value' => $internship,  'color' => '#F59E0B'],
            ['label' => 'Placements Achieved',      'value' => $placements,  'color' => '#EC4899'],
        ];
    }

    private function internshipStats(): array
    {
        $total    = Internship::count();
        $apps     = InternshipApplication::count();
        $approved = InternshipApplication::where('status', 'approved')->count();
        $completed= InternshipApplication::where('status', 'completed')->count();
        $pending  = InternshipApplication::where('status', 'pending')->count();

        return [
            ['label' => 'Total Internships',  'value' => $total,     'color' => '#F59E0B'],
            ['label' => 'Applications',       'value' => $apps,      'color' => '#3B82F6'],
            ['label' => 'Approved',           'value' => $approved,  'color' => '#10B981'],
            ['label' => 'Completed',          'value' => $completed, 'color' => '#8B5CF6'],
            ['label' => 'Pending Review',     'value' => $pending,   'color' => '#EC4899'],
        ];
    }

    private function placementStats(): array
    {
        $jobs        = Job::where('status', 'active')->count();
        $apps        = JobApplication::count();
        $shortlisted = JobApplication::where('status', 'shortlisted')->count();
        $interviews  = JobApplication::where('status', 'interview_scheduled')->count();
        $offers      = JobApplication::whereIn('status', ['offer_released', 'selected'])->count();

        return [
            ['label' => 'Jobs Posted',       'value' => $jobs,        'color' => '#10B981'],
            ['label' => 'Applications',      'value' => $apps,        'color' => '#3B82F6'],
            ['label' => 'Shortlisted',       'value' => $shortlisted, 'color' => '#F59E0B'],
            ['label' => 'Interviews',        'value' => $interviews,  'color' => '#8B5CF6'],
            ['label' => 'Offers Released',   'value' => $offers,      'color' => '#EC4899'],
        ];
    }

    private function monthlyEnrollments(): array
    {
        $data = CourseEnrollment::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return array_map(fn($m) => $data[$m] ?? 0, range(1, 12));
    }

    private function monthlyCompletions(): array
    {
        $data = CourseEnrollment::selectRaw('MONTH(updated_at) as month, COUNT(*) as count')
            ->where('status', 'completed')
            ->whereYear('updated_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return array_map(fn($m) => $data[$m] ?? 0, range(1, 12));
    }

    private function monthlyInternshipApps(): array
    {
        $data = InternshipApplication::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return array_map(fn($m) => $data[$m] ?? 0, range(1, 12));
    }

    private function monthlyPlacements(): array
    {
        $data = JobApplication::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereIn('status', ['offer_released', 'selected'])
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return array_map(fn($m) => $data[$m] ?? 0, range(1, 12));
    }
}
