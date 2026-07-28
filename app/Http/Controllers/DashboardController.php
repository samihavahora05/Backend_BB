<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InternshipApplication;
use App\Models\JobApplication;
use App\Models\Certificate;
use App\Models\CourseEnrollment;

class DashboardController extends Controller
{
    /**
     * Get real live statistics for Student Dashboard
     */
    public function getStudentStats(Request $request)
    {
        $user = $request->user();

        // 1. Get count of enrolled courses
        $activeCoursesCount = CourseEnrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        // 2. Fetch the actual list of enrolled courses
        $purchasedCourses = Course::whereIn('id', function($query) use ($user) {
            $query->select('course_id')
                ->from('course_enrollments')
                ->where('user_id', $user->id);
        })->with('category')->get();

        // 3. Count applications
        $activeInternshipApps = InternshipApplication::where('student_id', $user->id)->count();
        $activeJobApps = JobApplication::where('user_id', $user->id)->count();
        $totalApplications = $activeInternshipApps + $activeJobApps;

        // 4. Count certificates
        $certificatesCount = Certificate::where('user_id', $user->id)->count();

        // 5. Get recent applications list
        $recentInternshipApps = InternshipApplication::where('student_id', $user->id)
            ->with('internship')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($app) {
                return [
                    'id' => $app->id,
                    'role' => $app->internship->title ?? 'Internship',
                    'company' => 'Blueboxx Partner',
                    'status' => $app->status,
                    'statusColor' => $this->getStatusColor($app->status),
                    'appliedDate' => $app->created_at->diffForHumans()
                ];
            });

        $recentJobApps = JobApplication::where('user_id', $user->id)
            ->with('job')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($app) {
                return [
                    'id' => $app->id,
                    'role' => $app->job->title ?? 'Job',
                    'company' => 'Blueboxx Partner',
                    'status' => $app->status,
                    'statusColor' => $this->getStatusColor($app->status),
                    'appliedDate' => $app->created_at->diffForHumans()
                ];
            });

        $allApplications = $recentInternshipApps->concat($recentJobApps)->sortByDesc('appliedDate')->take(5)->values();

        return response()->json([
            'stats' => [
                'active_courses' => $activeCoursesCount,
                'completed_courses' => $certificatesCount, 
                'active_applications' => $totalApplications,
                'certificates_earned' => $certificatesCount
            ],
            'courses' => $purchasedCourses,
            'applications' => $allApplications
        ]);
    }

    /**
     * Get live notifications based on actual DB records
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = collect();

        // 1. Base Welcome Notification
        $notifications->push([
            'id' => 'welcome',
            'text' => "Welcome to Blueboxx DA, {$user->name}! Explore our courses and start learning.",
            'time' => $user->created_at->diffForHumans(),
            'read' => true
        ]);

        // 2. Internship Applications Notifications
        $internApps = InternshipApplication::where('student_id', $user->id)
            ->with('internship')
            ->latest()
            ->get();

        foreach ($internApps as $app) {
            $notifications->push([
                'id' => "intern-{$app->id}",
                'text' => "Your application for '{$app->internship->title}' is currently '{$app->status}'.",
                'time' => $app->created_at->diffForHumans(),
                'read' => ($app->status === 'applied')
            ]);
        }

        // 3. Job Applications Notifications
        $jobApps = JobApplication::where('user_id', $user->id)
            ->with('job')
            ->latest()
            ->get();

        foreach ($jobApps as $app) {
            $notifications->push([
                'id' => "job-{$app->id}",
                'text' => "Your application for '{$app->job->title}' is currently '{$app->status}'.",
                'time' => $app->created_at->diffForHumans(),
                'read' => ($app->status === 'applied')
            ]);
        }

        return response()->json($notifications->take(10));
    }

    private function getStatusColor($status)
    {
        switch (strtolower($status)) {
            case 'shortlisted':
            case 'interview':
                return 'bg-purple-50 text-purple-700';
            case 'hired':
            case 'approved':
                return 'bg-emerald-50 text-emerald-700';
            case 'rejected':
                return 'bg-rose-50 text-rose-700';
            default:
                return 'bg-blue-50 text-blue-700';
        }
    }
}
