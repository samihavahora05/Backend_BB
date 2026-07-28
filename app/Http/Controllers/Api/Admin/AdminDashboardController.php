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
                'revenue' => [
                    'total' => \App\Models\Order::where('status', 'completed')->sum('total_amount'), 
                    'monthly' => \App\Models\Order::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('total_amount')
                ],
                'orders' => [
                    'total' => \App\Models\Order::count(), 
                    'completed' => \App\Models\Order::where('status', 'completed')->count()
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
        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => [],
                'registrations' => []
            ]
        ]);
    }

    public function topCourses()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function recentEnrollments()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function feed()
    {
        return response()->json(['success' => true, 'data' => []]);
    }
}
