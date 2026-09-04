<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function summary()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.summary', 60, function () {
            return [
                'total_students' => \App\Models\User::role('student')->count(),
                'total_experts' => \App\Models\User::role('expert')->count(),
                'total_companies' => \App\Models\User::role('company')->count(),
                'courses' => [
                    'total' => \App\Models\Course::count(), 
                    'published' => \App\Models\Course::where('is_published', true)->count()
                ],
                'leads' => [
                    'total' => \App\Models\Lead::count(), 
                    'new' => \App\Models\Lead::where('status', 'new')->count()
                ],
                'orders' => [
                    'total' => \App\Models\Order::count(), 
                    'completed' => \App\Models\Order::where('status', 'completed')->count()
                ],
                'enrollments' => [
                    'total' => \App\Models\CourseEnrollment::count(),
                    'active' => \App\Models\CourseEnrollment::where('status', 'active')->count()
                ],
                'jobs' => [
                    'total' => \App\Models\Job::count(), 
                    'active' => \App\Models\Job::where('status', 'active')->count()
                ],
                'internships' => [
                    'total' => \App\Models\Internship::count(), 
                    'running' => \App\Models\Internship::where('status', 'active')->count()
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function charts()
    {
        $enrollments = [];
        $students = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = now()->month($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $enrolls = \App\Models\Order::where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
                
            $studs = \App\Models\User::role('student')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $enrollments[] = [
                'month' => $month->format('M'),
                'count' => $enrolls
            ];
            
            $students[] = [
                'month' => $month->format('M'),
                'count' => $studs
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enrollments' => $enrollments,
                'students' => $students
            ]
        ]);
    }

    public function topCourses()
    {
        $courses = \App\Models\Course::leftJoin('order_items', function($join) {
                $join->on('courses.id', '=', 'order_items.purchasable_id')
                     ->where('order_items.purchasable_type', \App\Models\Course::class);
            })
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('courses.id, courses.title, courses.price, count(case when orders.status = "completed" then 1 end) as enrollments_count')
            ->groupBy('courses.id', 'courses.title', 'courses.price')
            ->orderByDesc('enrollments_count')
            ->orderByDesc('courses.id')
            ->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'price' => $course->price,
                    'enrollments_count' => (int) $course->enrollments_count
                ];
            });

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function recentEnrollments()
    {
        try {
            $enrollments = \App\Models\CourseEnrollment::with(['user', 'course'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($enrollment) {
                    $userName = $enrollment->user?->name ?? trim(($enrollment->user?->first_name ?? '') . ' ' . ($enrollment->user?->last_name ?? ''));
                    if (empty($userName)) $userName = 'Student';

                    return [
                        'id' => $enrollment->id,
                        'user' => [
                            'first_name' => $enrollment->user?->first_name ?? $userName,
                            'last_name' => $enrollment->user?->last_name ?? '',
                            'name' => $userName,
                            'email' => $enrollment->user?->email ?? '',
                        ],
                        'items' => [
                            [
                                'course' => [
                                    'title' => $enrollment->course?->title ?? 'Course',
                                    'id' => $enrollment->course_id,
                                ]
                            ]
                        ],
                        'payment_status' => $enrollment->status ?? 'completed',
                        'created_at' => $enrollment->created_at ? $enrollment->created_at->toISOString() : now()->toISOString(),
                    ];
                });

            return response()->json(['success' => true, 'data' => $enrollments]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard recent enrollments error: ' . $e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    }

    public function feed()
    {
        $activities = collect();

        \App\Models\User::latest()->take(15)->get()->each(function($user) use ($activities) {
            $activities->push([
                'id' => 'u_'.$user->id,
                'admin' => ['first_name' => $user->first_name ?? $user->name ?? 'User', 'last_name' => $user->last_name ?? ''],
                'action' => 'created a new account',
                'table_name' => 'Platform',
                'created_at' => $user->created_at,
            ]);
        });

        \App\Models\Course::latest()->take(15)->get()->each(function($course) use ($activities) {
            $activities->push([
                'id' => 'c_'.$course->id,
                'admin' => ['first_name' => 'Admin', 'last_name' => 'System'],
                'action' => 'published a new course',
                'table_name' => $course->title,
                'created_at' => $course->created_at,
            ]);
        });

        \App\Models\Order::with('user')->latest()->take(15)->get()->each(function($order) use ($activities) {
            $activities->push([
                'id' => 'o_'.$order->id,
                'admin' => ['first_name' => $order->user->first_name ?? 'Someone', 'last_name' => $order->user->last_name ?? ''],
                'action' => 'placed a new order',
                'table_name' => 'Store',
                'created_at' => $order->created_at,
            ]);
        });

        \App\Models\Lead::latest()->take(15)->get()->each(function($lead) use ($activities) {
            $activities->push([
                'id' => 'l_'.$lead->id,
                'admin' => ['first_name' => 'New', 'last_name' => 'Lead'],
                'action' => 'submitted an enquiry',
                'table_name' => 'Website',
                'created_at' => $lead->created_at,
            ]);
        });

        \App\Models\ActivityLog::with('user')->latest()->take(15)->get()->each(function($log) use ($activities) {
            $activities->push([
                'id' => 'al_'.$log->id,
                'admin' => [
                    'first_name' => $log->user->first_name ?? $log->user->name ?? 'System',
                    'last_name' => $log->user->last_name ?? '',
                ],
                'action' => $log->action,
                'table_name' => $log->description ?? 'System Record',
                'created_at' => $log->created_at,
            ]);
        });

        $sortedLogs = $activities->sortByDesc('created_at')->values()->take(50);

        return response()->json(['success' => true, 'data' => $sortedLogs]);
    }
}
