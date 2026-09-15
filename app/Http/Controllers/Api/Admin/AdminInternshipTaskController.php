<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission;
use App\Models\InternshipApplication;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInternshipTaskController extends Controller
{
    /**
     * 1. Global Task List across all companies
     * GET /api/admin/internships/tasks
     */
    public function index(Request $request)
    {
        $query = InternshipTask::with([
            'company:id,first_name,last_name,name,email',
            'assignedTo:id,first_name,last_name,name,email',
            'assignedBy:id,first_name,last_name,name',
            'internship:id,title,category,company_name',
            'latestSubmission',
        ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'overdue') {
                $query->where('due_date', '<', now())->whereNotIn('status', ['completed', 'approved']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%"))
                  ->orWhereHas('assignedTo', fn($uq) => $uq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $tasks = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $tasks,
        ]);
    }

    /**
     * 2. Global Platform Task Stats
     * GET /api/admin/internships/tasks/stats
     */
    public function stats()
    {
        $allTasks = InternshipTask::all();
        $evaluatedTasks = $allTasks->where('status', 'completed')->whereNotNull('marks');
        $avgScore = $evaluatedTasks->count() > 0 ? round($evaluatedTasks->avg('marks'), 1) : null;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_tasks'      => $allTasks->count(),
                'assigned'         => $allTasks->where('status', 'assigned')->count(),
                'in_progress'      => $allTasks->where('status', 'in_progress')->count(),
                'pending_review'   => $allTasks->whereIn('status', ['submitted', 'under_review'])->count(),
                'completed'        => $allTasks->where('status', 'completed')->count(),
                'changes_required' => $allTasks->where('status', 'changes_required')->count(),
                'overdue'          => $allTasks->filter(fn($t) => $t->is_overdue)->count(),
                'average_score'    => $avgScore,
            ]
        ]);
    }

    /**
     * 3. View Single Task Detail
     * GET /api/admin/internships/tasks/{id}
     */
    public function show($id)
    {
        $task = InternshipTask::with([
            'company',
            'assignedTo',
            'assignedBy',
            'internship',
            'submissions.user',
            'submissions.reviewer',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $task,
        ]);
    }

    /**
     * 4. Create Task (Admin override)
     * POST /api/admin/internships/tasks
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'instructions'         => 'nullable|string',
            'expected_deliverable' => 'nullable|string',
            'assigned_to'          => 'required|integer|exists:users,id',
            'company_id'           => 'nullable|integer|exists:users,id',
            'internship_id'        => 'required|integer|exists:internships,id',
            'priority'             => 'required|in:low,medium,high,urgent',
            'start_date'           => 'nullable|date',
            'due_date'             => 'required|date',
            'attachments'          => 'nullable|array',
        ]);

        $task = InternshipTask::create([
            'company_id'           => $validated['company_id'] ?? null,
            'internship_id'        => $validated['internship_id'],
            'assigned_to'          => $validated['assigned_to'],
            'assigned_by'          => $request->user()->id,
            'title'                => trim($validated['title']),
            'description'          => trim($validated['description']),
            'instructions'         => $validated['instructions'] ?? null,
            'expected_deliverable' => $validated['expected_deliverable'] ?? null,
            'priority'             => $validated['priority'],
            'start_date'           => $validated['start_date'] ?? now(),
            'due_date'             => $validated['due_date'],
            'attachments'          => $validated['attachments'] ?? [],
            'status'               => 'assigned',
            'max_marks'            => 100,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully by Admin.',
            'data'    => $task,
        ], 201);
    }

    /**
     * 5. Update Task
     * PUT /api/admin/internships/tasks/{id}
     */
    public function update(Request $request, $id)
    {
        $task = InternshipTask::findOrFail($id);
        $task->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data'    => $task,
        ]);
    }

    /**
     * 6. Delete Task
     * DELETE /api/admin/internships/tasks/{id}
     */
    public function destroy($id)
    {
        $task = InternshipTask::findOrFail($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * 7. Admin Review & Grade Submission
     * POST /api/admin/internships/tasks/{id}/review
     */
    public function review(Request $request, $id)
    {
        $adminId = $request->user()->id;
        $task = InternshipTask::findOrFail($id);

        $validated = $request->validate([
            'action'   => 'required|in:approve,request_changes',
            'marks'    => 'required_if:action,approve|nullable|numeric|min:0|max:100',
            'feedback' => 'required_if:action,request_changes|nullable|string|max:3000',
        ]);

        $latestSubmission = InternshipSubmission::where('task_id', $task->id)->latest('version')->first();

        if ($validated['action'] === 'approve') {
            $task->update([
                'status'       => 'completed',
                'marks'        => $validated['marks'] ?? 100,
                'feedback'     => $validated['feedback'] ?? 'Approved by Admin.',
                'completed_at' => now(),
            ]);

            if ($latestSubmission) {
                $latestSubmission->update([
                    'status'         => 'approved',
                    'marks_obtained' => $validated['marks'] ?? 100,
                    'feedback'       => $validated['feedback'] ?? 'Approved by Admin.',
                    'reviewer_id'    => $adminId,
                    'reviewed_at'    => now(),
                ]);
            }
        } else {
            $task->update([
                'status'   => 'changes_required',
                'feedback' => $validated['feedback'],
            ]);

            if ($latestSubmission) {
                $latestSubmission->update([
                    'status'      => 'resubmit',
                    'feedback'    => $validated['feedback'],
                    'reviewer_id' => $adminId,
                    'reviewed_at' => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Review recorded successfully.',
            'data'    => $task->fresh(['assignedTo', 'submissions']),
        ]);
    }

    /**
     * 8. List Approved Company-Intern Pairs globally
     * GET /api/admin/internships/approved-interns
     */
    public function approvedInterns()
    {
        $pairs = InternshipApplication::with(['user', 'internship.company'])
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get()
            ->map(function ($app) {
                return [
                    'application_id'   => $app->id,
                    'intern_id'        => $app->user_id,
                    'intern_name'      => $app->applicant_name,
                    'intern_email'     => $app->applicant_email,
                    'company_id'       => $app->internship?->company_id,
                    'company_name'     => $app->internship?->company_name ?? 'Blueboxx Partner',
                    'internship_id'    => $app->internship_id,
                    'internship_title' => $app->internship?->title,
                    'approved_at'      => $app->approved_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $pairs,
        ]);
    }

    /**
     * 9. Global Intern Performance Overview
     * GET /api/admin/internships/performance
     */
    public function performance(Request $request)
    {
        $query = InternshipApplication::with(['user', 'internship.company.companyProfile'])
            ->where('status', 'approved');

        if ($request->filled('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        if ($request->filled('company_id')) {
            $companyId = $request->company_id;
            $query->whereHas('internship', fn($q) => $q->where('company_id', $companyId));
        }

        $applications = $query->latest('approved_at')->get();
        $internIds = $applications->pluck('user_id')->filter()->unique()->toArray();

        $allTasks = InternshipTask::with(['latestSubmission'])
            ->whereIn('assigned_to', $internIds)
            ->get();

        $interns = $applications->map(function ($app) use ($allTasks) {
            $userId = $app->user_id ?? $app->user?->id;
            $tasks = $allTasks->where('assigned_to', $userId);

            $totalTasks       = $tasks->count();
            $completedTasks   = $tasks->where('status', 'completed')->count();
            $pendingReview    = $tasks->whereIn('status', ['submitted', 'under_review'])->count();
            $changesRequired  = $tasks->where('status', 'changes_required')->count();
            $overdueTasks     = $tasks->filter(fn($t) => $t->is_overdue)->count();

            $evaluatedTasks = $tasks->where('status', 'completed')->whereNotNull('marks');
            $evaluatedCount = $evaluatedTasks->count();
            $avgMarks       = $evaluatedCount > 0 ? round($evaluatedTasks->avg('marks'), 1) : null;

            $category = null;
            if ($avgMarks !== null) {
                if ($avgMarks >= 90)      $category = 'Excellent';
                elseif ($avgMarks >= 75) $category = 'Very Good';
                elseif ($avgMarks >= 60) $category = 'Good';
                elseif ($avgMarks >= 40) $category = 'Needs Improvement';
                else                     $category = 'Poor';
            }

            $user = $app->user;
            $companyName = $app->internship?->company?->companyProfile?->company_name
                ?? $app->internship?->company_name
                ?? $app->internship?->company?->name
                ?? 'Blueboxx Partner';

            $companyLogo = $app->internship?->company?->companyProfile?->logo
                ?? $app->internship?->company_logo
                ?? null;

            return [
                'application_id'       => $app->id,
                'intern_id'            => $userId,
                'first_name'           => $user?->first_name ?? $app->first_name,
                'last_name'            => $user?->last_name ?? $app->last_name,
                'name'                 => trim(($user?->first_name ?? $app->first_name) . ' ' . ($user?->last_name ?? $app->last_name)),
                'email'                => $user?->email ?? $app->email,
                'avatar'               => $user?->avatar_url ?? null,
                'company_id'           => $app->internship?->company_id,
                'company_name'         => $companyName,
                'company_logo'         => $companyLogo ? asset('storage/' . $companyLogo) : null,
                'internship_id'        => $app->internship_id,
                'internship_title'     => $app->internship?->title ?? 'Internship',
                'start_date'           => $app->internship?->start_date?->toDateString(),
                'end_date'             => $app->internship?->end_date?->toDateString(),
                'approved_at'          => $app->approved_at?->toIso8601String(),
                'internship_status'    => $app->status,
                'total_tasks'          => $totalTasks,
                'completed_tasks'      => $completedTasks,
                'pending_review_tasks' => $pendingReview,
                'changes_required'     => $changesRequired,
                'overdue_tasks'        => $overdueTasks,
                'evaluated_tasks_count'=> $evaluatedCount,
                'average_marks'        => $avgMarks,
                'performance_category' => $category,
                'requires_attention'   => ($changesRequired > 0 || $overdueTasks > 0 || ($avgMarks !== null && $avgMarks < 50)),
            ];
        });

        // Filter search in-memory if provided
        if ($request->filled('search')) {
            $s = strtolower($request->search);
            $interns = $interns->filter(function ($i) use ($s) {
                return str_contains(strtolower($i['name']), $s)
                    || str_contains(strtolower($i['email']), $s)
                    || str_contains(strtolower($i['company_name']), $s)
                    || str_contains(strtolower($i['internship_title']), $s);
            })->values();
        }

        // Filter performance range
        if ($request->filled('performance_range') && $request->performance_range !== 'all') {
            $range = $request->performance_range;
            $interns = $interns->filter(function ($i) use ($range) {
                if ($range === 'not_evaluated') return $i['average_marks'] === null;
                if ($range === 'excellent')     return $i['average_marks'] !== null && $i['average_marks'] >= 90;
                if ($range === 'very_good')     return $i['average_marks'] !== null && $i['average_marks'] >= 75 && $i['average_marks'] < 90;
                if ($range === 'good')          return $i['average_marks'] !== null && $i['average_marks'] >= 60 && $i['average_marks'] < 75;
                if ($range === 'needs_improvement') return $i['average_marks'] !== null && $i['average_marks'] >= 40 && $i['average_marks'] < 60;
                if ($range === 'poor')          return $i['average_marks'] !== null && $i['average_marks'] < 40;
                return true;
            })->values();
        }

        // Compute overall summary stats
        $evaluatedInterns = $interns->filter(fn($i) => $i['average_marks'] !== null);
        $overallAvg = $evaluatedInterns->count() > 0 ? round($evaluatedInterns->avg('average_marks'), 1) : null;
        $highest = $evaluatedInterns->sortByDesc('average_marks')->first();

        $summary = [
            'total_interns'               => $interns->count(),
            'active_interns'              => $interns->where('internship_status', 'approved')->count(),
            'completed_internships'       => $interns->where('internship_status', 'completed')->count(),
            'pending_evaluation_interns'  => $interns->where('pending_review_tasks', '>', 0)->count(),
            'overall_average_performance' => $overallAvg,
            'highest_performer'           => $highest ? [
                'name'          => $highest['name'],
                'company_name'  => $highest['company_name'],
                'average_marks' => $highest['average_marks'],
                'category'      => $highest['performance_category'],
            ] : null,
            'tasks_completed'             => $allTasks->where('status', 'completed')->count(),
            'tasks_pending_review'        => $allTasks->whereIn('status', ['submitted', 'under_review'])->count(),
            'interns_requiring_attention' => $interns->where('requires_attention', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $interns,
        ]);
    }

    /**
     * 10. View Single Intern Detailed Performance Breakdown
     * GET /api/admin/internships/performance/{internId}
     */
    public function internPerformanceDetail(Request $request, $internId)
    {
        $app = InternshipApplication::with(['user', 'internship.company.companyProfile'])
            ->where('status', 'approved')
            ->where(function($q) use ($internId) {
                $q->where('user_id', $internId)->orWhere('id', $internId);
            })
            ->firstOrFail();

        $userId = $app->user_id ?? $app->user?->id;

        $tasks = InternshipTask::with(['latestSubmission.reviewer', 'submissions.reviewer', 'assignedBy'])
            ->where('assigned_to', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $evaluatedTasks = $tasks->where('status', 'completed')->whereNotNull('marks')->sortBy('completed_at')->values();
        $avgScore = $evaluatedTasks->count() > 0 ? round($evaluatedTasks->avg('marks'), 1) : null;

        // Calculate score trend
        $trendHistory = $evaluatedTasks->map(function ($t) {
            return [
                'task_id'      => $t->id,
                'title'        => $t->title,
                'marks'        => $t->marks,
                'completed_at' => $t->completed_at ? $t->completed_at->toFormattedDateString() : $t->updated_at->toFormattedDateString(),
            ];
        });

        $trendStatus = 'Insufficient Data';
        if ($evaluatedTasks->count() >= 2) {
            $firstScore = $evaluatedTasks->first()->marks;
            $lastScore  = $evaluatedTasks->last()->marks;
            $diff = $lastScore - $firstScore;
            if ($diff >= 5) {
                $trendStatus = 'Improving';
            } elseif ($diff <= -5) {
                $trendStatus = 'Declining';
            } else {
                $trendStatus = 'Stable';
            }
        }

        $category = null;
        if ($avgScore !== null) {
            if ($avgScore >= 90)      $category = 'Excellent';
            elseif ($avgScore >= 75) $category = 'Very Good';
            elseif ($avgScore >= 60) $category = 'Good';
            elseif ($avgScore >= 40) $category = 'Needs Improvement';
            else                     $category = 'Poor';
        }

        $companyName = $app->internship?->company?->companyProfile?->company_name
            ?? $app->internship?->company_name
            ?? $app->internship?->company?->name
            ?? 'Blueboxx Partner';

        return response()->json([
            'success' => true,
            'data'    => [
                'intern' => [
                    'id'         => $userId,
                    'first_name' => $app->user?->first_name ?? $app->first_name,
                    'last_name'  => $app->user?->last_name ?? $app->last_name,
                    'name'       => trim(($app->user?->first_name ?? $app->first_name) . ' ' . ($app->user?->last_name ?? $app->last_name)),
                    'email'      => $app->user?->email ?? $app->email,
                    'phone'      => $app->user?->phone ?? $app->phone,
                    'avatar'     => $app->user?->avatar_url ?? null,
                    'degree'     => $app->degree,
                    'resume_url' => $app->resume_url,
                ],
                'company' => [
                    'id'   => $app->internship?->company_id,
                    'name' => $companyName,
                ],
                'internship'  => $app->internship,
                'application' => $app,
                'metrics'     => [
                    'total_tasks'          => $tasks->count(),
                    'assigned'             => $tasks->where('status', 'assigned')->count(),
                    'in_progress'          => $tasks->where('status', 'in_progress')->count(),
                    'pending_review'       => $tasks->whereIn('status', ['submitted', 'under_review'])->count(),
                    'completed'            => $tasks->where('status', 'completed')->count(),
                    'changes_required'     => $tasks->where('status', 'changes_required')->count(),
                    'overdue'              => $tasks->filter(fn($t) => $t->is_overdue)->count(),
                    'evaluated_count'      => $evaluatedTasks->count(),
                    'average_marks'        => $avgScore,
                    'performance_category' => $category,
                    'trend_status'         => $trendStatus,
                    'trend_history'        => $trendHistory,
                ],
                'tasks' => $tasks,
            ]
        ]);
    }
}
