<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Models\User;
use App\Models\Course;
use App\Models\Job;
use App\Models\Internship;
use App\Models\Order;
use App\Models\DataAuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getPlatformSummary(): array
    {
        return [
            'total_students' => User::role('student')->count(),
            'total_experts' => User::role('expert')->count(),
            'total_companies' => User::role('company')->count(),
            'total_colleges' => User::role('college')->count(),
            
            'courses' => [
                'total' => Course::count(),
                'published' => Course::where('status', 'published')->count(),
                'draft' => Course::where('status', 'draft')->count(),
                'pending' => Course::where('status', 'pending')->count(),
            ],
            
            'jobs' => [
                'total' => Job::count(),
                'active' => Job::where('status', 'open')->count(),
                'expired' => Job::where('status', 'closed')->count(),
                'pending' => Job::where('status', 'draft')->count(),
            ],
            
            'internships' => [
                'total' => Internship::count(),
                'running' => Internship::where('status', 'open')->count(),
                'completed' => Internship::where('status', 'closed')->count(),
                'pending' => Internship::where('status', 'draft')->count(),
            ],
            
            'revenue' => [
                'total' => Order::where('status', 'completed')->sum('total_amount'),
                'monthly' => Order::where('status', 'completed')
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('total_amount'),
                'today' => Order::where('status', 'completed')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('total_amount'),
            ],
            
            'orders' => [
                'total' => Order::count(),
                'completed' => Order::where('status', 'completed')->count(),
                'pending' => Order::where('status', 'pending')->count(),
            ],
            
            'users_active_today' => User::whereDate('last_login_at', Carbon::today())->count(),
        ];
    }

    public function getRevenueChartData(string $period = 'monthly'): array
    {
        $dateSql = DB::connection()->getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', created_at)" 
            : "DATE_FORMAT(created_at, '%Y-%m')";

        // Simple 12-month grouping
        return Order::select(
            DB::raw('SUM(total_amount) as revenue'),
            DB::raw("$dateSql as month")
        )
        ->where('status', 'completed')
        ->where('created_at', '>=', Carbon::now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get()
        ->toArray();
    }

    public function getRegistrationChartData(string $period = 'monthly'): array
    {
        $dateSql = DB::connection()->getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', created_at)" 
            : "DATE_FORMAT(created_at, '%Y-%m')";

        return User::select(
            DB::raw('COUNT(id) as registrations'),
            DB::raw("$dateSql as month")
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get()
        ->toArray();
    }

    public function getLatestActivity(int $limit = 10): array
    {
        // Utilizing the audit logs generated in the Enterprise Upgrade
        return DataAuditLog::with('admin:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getRecentData(string $module, int $limit = 5): array
    {
        return match ($module) {
            'enrollments' => Order::with('user', 'items.course')->latest()->limit($limit)->get()->toArray(),
            'companies' => User::role('company')->latest()->limit($limit)->get()->toArray(),
            'students' => User::role('student')->latest()->limit($limit)->get()->toArray(),
            default => [],
        };
    }

    public function getTopLists(string $module, int $limit = 5): array
    {
        return match ($module) {
            'courses' => Course::withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->limit($limit)->get()->toArray(),
            'instructors' => User::role('expert')
                ->withCount('courses')
                ->orderBy('courses_count', 'desc')
                ->limit($limit)->get()->toArray(),
            default => [],
        };
    }
}
