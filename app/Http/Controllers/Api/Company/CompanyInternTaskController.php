<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CompanyInternTaskController extends Controller
{
    /**
     * Get the authenticated company ID
     */
    private function getCompanyId(Request $request): int
    {
        return $request->user()->id;
    }

    /**
     * Helper to get IDs of all internships owned by this company
     */
    private function getCompanyInternshipIds(int $companyId): array
    {
        return Internship::where('company_id', $companyId)->pluck('id')->toArray();
    }

    /**
     * 1. List Internship Applications for this Company
     * GET /api/company/internships/applications
     */
    public function applications(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $query = InternshipApplication::with(['user:id,first_name,last_name,email,phone', 'internship:id,title,category,company_name,location'])
            ->whereIn('internship_id', $internshipIds);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('internship', fn($iq) => $iq->where('title', 'like', "%{$search}%"));
            });
        }

        $applications = $query->latest('applied_at')->paginate($request->input('per_page', 20));

        $stats = [
            'total'         => InternshipApplication::whereIn('internship_id', $internshipIds)->count(),
            'pending'       => InternshipApplication::whereIn('internship_id', $internshipIds)->whereIn('status', ['applied', 'under_review', 'pending'])->count(),
            'approved'      => InternshipApplication::whereIn('internship_id', $internshipIds)->where('status', 'approved')->count(),
            'rejected'      => InternshipApplication::whereIn('internship_id', $internshipIds)->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $applications,
            'stats'   => $stats,
        ]);
    }

    /**
     * 2. View Single Application Detail
     * GET /api/company/internships/applications/{id}
     */
    public function showApplication(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $app = InternshipApplication::with(['user', 'internship', 'appointmentLetter'])
            ->whereIn('internship_id', $internshipIds)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $app,
        ]);
    }

    /**
     * 3. Approve Internship Application
     * POST /api/company/internships/applications/{id}/approve
     */
    public function approveApplication(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $app = InternshipApplication::whereIn('internship_id', $internshipIds)->findOrFail($id);

        $app->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'reviewed_by' => $companyId,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Internship application approved. Intern is now assigned to your company.',
            'data'    => $app,
        ]);
    }

    /**
     * 4. Reject Internship Application
     * POST /api/company/internships/applications/{id}/reject
     */
    public function rejectApplication(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $app = InternshipApplication::whereIn('internship_id', $internshipIds)->findOrFail($id);

        $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $app->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => $companyId,
            'reviewed_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Internship application rejected.',
            'data'    => $app,
        ]);
    }

    /**
     * 5. List Approved/Assigned Interns (My Interns)
     * GET /api/company/interns
     */
    public function interns(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        // Fetch ONLY approved applications for this company's internships
        $approvedApps = InternshipApplication::with(['user', 'internship'])
            ->whereIn('internship_id', $internshipIds)
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get();

        $interns = $approvedApps->map(function ($app) use ($companyId) {
            $user = $app->user;
            $userId = $user?->id ?? $app->user_id;

            // Fetch actual tasks assigned to this intern by this company
            $tasks = InternshipTask::where('company_id', $companyId)
                ->where('assigned_to', $userId)
                ->get();

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', 'completed')->count();
            $pendingTasks = $tasks->whereIn('status', ['assigned', 'in_progress', 'submitted', 'under_review', 'changes_required'])->count();

            $evaluatedTasks = $tasks->where('status', 'completed')->whereNotNull('marks');
            $avgScore = $evaluatedTasks->count() > 0 ? round($evaluatedTasks->avg('marks'), 1) : null;

            return [
                'application_id'   => $app->id,
                'intern_id'        => $userId,
                'first_name'       => $user?->first_name ?? $app->first_name,
                'last_name'        => $user?->last_name ?? $app->last_name,
                'name'             => trim(($user?->first_name ?? $app->first_name) . ' ' . ($user?->last_name ?? $app->last_name)),
                'email'            => $user?->email ?? $app->email,
                'phone'            => $user?->phone ?? $app->phone,
                'degree'           => $app->degree,
                'internship_id'    => $app->internship_id,
                'internship_title' => $app->internship?->title ?? 'Internship',
                'start_date'       => $app->internship?->start_date?->toDateString(),
                'end_date'         => $app->internship?->end_date?->toDateString(),
                'approved_at'      => $app->approved_at?->toIso8601String(),
                'status'           => 'Active',
                'total_tasks'      => $totalTasks,
                'completed_tasks'  => $completedTasks,
                'pending_tasks'    => $pendingTasks,
                'average_marks'    => $avgScore,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $interns,
            'stats'   => [
                'total_interns'    => $interns->count(),
                'total_tasks'      => InternshipTask::where('company_id', $companyId)->count(),
                'completed_tasks'  => InternshipTask::where('company_id', $companyId)->where('status', 'completed')->count(),
            ]
        ]);
    }

    /**
     * 6. View Single Intern Details
     * GET /api/company/interns/{id}
     */
    public function internDetail(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        // Verify that the requested user is an approved intern of this company
        $app = InternshipApplication::with(['user', 'internship'])
            ->whereIn('internship_id', $internshipIds)
            ->where('status', 'approved')
            ->where(function($q) use ($id) {
                $q->where('user_id', $id)->orWhere('id', $id);
            })
            ->firstOrFail();

        $userId = $app->user_id ?? $app->user?->id;

        $tasks = InternshipTask::with(['latestSubmission', 'submissions'])
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $completedCount = $tasks->where('status', 'completed')->count();
        $evaluatedTasks = $tasks->where('status', 'completed')->whereNotNull('marks');
        $avgScore = $evaluatedTasks->count() > 0 ? round($evaluatedTasks->avg('marks'), 1) : null;

        return response()->json([
            'success' => true,
            'data'    => [
                'intern'           => [
                    'id'         => $userId,
                    'first_name' => $app->user?->first_name ?? $app->first_name,
                    'last_name'  => $app->user?->last_name ?? $app->last_name,
                    'name'       => trim(($app->user?->first_name ?? $app->first_name) . ' ' . ($app->user?->last_name ?? $app->last_name)),
                    'email'      => $app->user?->email ?? $app->email,
                    'phone'      => $app->user?->phone ?? $app->phone,
                    'degree'     => $app->degree,
                    'resume_url' => $app->resume_url,
                ],
                'internship'       => $app->internship,
                'application'      => $app,
                'tasks'            => $tasks,
                'metrics'          => [
                    'total_tasks'     => $tasks->count(),
                    'assigned'        => $tasks->where('status', 'assigned')->count(),
                    'in_progress'     => $tasks->where('status', 'in_progress')->count(),
                    'pending_review'  => $tasks->whereIn('status', ['submitted', 'under_review'])->count(),
                    'completed'       => $completedCount,
                    'changes_required'=> $tasks->where('status', 'changes_required')->count(),
                    'overdue'         => $tasks->filter(fn($t) => $t->is_overdue)->count(),
                    'average_marks'   => $avgScore,
                ]
            ]
        ]);
    }

    /**
     * 7. List Tasks for this Company
     * GET /api/company/tasks
     */
    public function tasks(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $query = InternshipTask::with([
            'assignedTo:id,first_name,last_name,email',
            'internship:id,title,category',
            'latestSubmission',
        ])->where('company_id', $companyId);

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

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('assignedTo', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $tasks = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        $allTasks = InternshipTask::where('company_id', $companyId)->get();

        $stats = [
            'total'            => $allTasks->count(),
            'assigned'         => $allTasks->where('status', 'assigned')->count(),
            'in_progress'      => $allTasks->where('status', 'in_progress')->count(),
            'pending_review'   => $allTasks->whereIn('status', ['submitted', 'under_review'])->count(),
            'completed'        => $allTasks->where('status', 'completed')->count(),
            'changes_required' => $allTasks->where('status', 'changes_required')->count(),
            'overdue'          => $allTasks->filter(fn($t) => $t->is_overdue)->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $tasks,
            'stats'   => $stats,
        ]);
    }

    /**
     * 8. Create & Assign Task
     * POST /api/company/tasks
     */
    public function storeTask(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'instructions'         => 'nullable|string',
            'expected_deliverable' => 'nullable|string',
            'assigned_to'          => 'required|integer|exists:users,id',
            'internship_id'        => 'nullable|integer|exists:internships,id',
            'priority'             => 'required|in:low,medium,high,urgent',
            'start_date'           => 'nullable|date',
            'due_date'             => 'required|date|after_or_equal:start_date',
            'attachments'          => 'nullable|array',
            'attachments.*'        => 'nullable|string',
        ]);

        // Strict Authorization: Verify the assigned_to user is an approved intern of this company
        $isApprovedIntern = InternshipApplication::whereIn('internship_id', $internshipIds)
            ->where('user_id', $validated['assigned_to'])
            ->where('status', 'approved')
            ->exists();

        if (!$isApprovedIntern) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: The selected candidate is not an approved intern of your company.',
            ], 403);
        }

        $internshipId = $validated['internship_id'] ?? null;
        if (!$internshipId) {
            $internshipId = InternshipApplication::whereIn('internship_id', $internshipIds)
                ->where('user_id', $validated['assigned_to'])
                ->where('status', 'approved')
                ->value('internship_id');
        }

        $task = InternshipTask::create([
            'company_id'           => $companyId,
            'internship_id'        => $internshipId,
            'assigned_to'          => $validated['assigned_to'],
            'assigned_by'          => $companyId,
            'title'                => trim($validated['title']),
            'description'          => trim($validated['description']),
            'instructions'         => !empty($validated['instructions']) ? trim($validated['instructions']) : null,
            'expected_deliverable' => !empty($validated['expected_deliverable']) ? trim($validated['expected_deliverable']) : null,
            'priority'             => $validated['priority'],
            'start_date'           => $validated['start_date'] ?? now(),
            'due_date'             => $validated['due_date'],
            'attachments'          => $validated['attachments'] ?? [],
            'status'               => 'assigned',
            'max_marks'            => 100,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created and assigned successfully.',
            'data'    => $task->load(['assignedTo:id,first_name,last_name,email', 'internship:id,title']),
        ], 201);
    }

    /**
     * 9. View Single Task Detail
     * GET /api/company/tasks/{id}
     */
    public function showTask(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);

        $task = InternshipTask::with([
            'assignedTo:id,first_name,last_name,email,phone',
            'internship:id,title,category',
            'submissions.user:id,first_name,last_name,email',
            'submissions.reviewer:id,first_name,last_name',
        ])->where('company_id', $companyId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $task,
        ]);
    }

    /**
     * 10. Update Task
     * PUT /api/company/tasks/{id}
     */
    public function updateTask(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $task = InternshipTask::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'title'                => 'sometimes|required|string|max:255',
            'description'          => 'sometimes|required|string',
            'instructions'         => 'nullable|string',
            'expected_deliverable' => 'nullable|string',
            'priority'             => 'sometimes|required|in:low,medium,high,urgent',
            'start_date'           => 'nullable|date',
            'due_date'             => 'sometimes|required|date',
            'attachments'          => 'nullable|array',
        ]);

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data'    => $task,
        ]);
    }

    /**
     * 11. Delete Task
     * DELETE /api/company/tasks/{id}
     */
    public function deleteTask(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $task = InternshipTask::where('company_id', $companyId)->findOrFail($id);

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * 12. Review Task Submission (Approve or Request Changes)
     * POST /api/company/tasks/{id}/review
     */
    public function reviewSubmission(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $task = InternshipTask::where('company_id', $companyId)->findOrFail($id);

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
                'feedback'     => $validated['feedback'] ?? 'Task approved.',
                'completed_at' => now(),
            ]);

            if ($latestSubmission) {
                $latestSubmission->update([
                    'status'         => 'approved',
                    'marks_obtained' => $validated['marks'] ?? 100,
                    'feedback'       => $validated['feedback'] ?? 'Task approved.',
                    'reviewer_id'    => $companyId,
                    'reviewed_at'    => now(),
                ]);
            }

            $message = 'Task approved and marks awarded successfully.';
        } else {
            $task->update([
                'status'   => 'changes_required',
                'feedback' => $validated['feedback'],
            ]);

            if ($latestSubmission) {
                $latestSubmission->update([
                    'status'      => 'resubmit',
                    'feedback'    => $validated['feedback'],
                    'reviewer_id' => $companyId,
                    'reviewed_at' => now(),
                ]);
            }

            $message = 'Change request sent to the intern with feedback.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $task->fresh(['assignedTo', 'submissions']),
        ]);
    }

    /**
     * 10. Company Intern Performance Overview (Strictly scoped to logged-in company)
     * GET /api/company/performance
     */
    public function performance(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        $query = InternshipApplication::with(['user', 'internship'])
            ->whereIn('internship_id', $internshipIds)
            ->where('status', 'approved');

        if ($request->filled('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        $applications = $query->latest('approved_at')->get();
        $internIds = $applications->pluck('user_id')->filter()->unique()->toArray();

        $allTasks = InternshipTask::with(['latestSubmission'])
            ->where('company_id', $companyId)
            ->whereIn('assigned_to', $internIds)
            ->get();

        $interns = $applications->map(function ($app) use ($allTasks, $companyId) {
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

            return [
                'application_id'       => $app->id,
                'intern_id'            => $userId,
                'first_name'           => $user?->first_name ?? $app->first_name,
                'last_name'            => $user?->last_name ?? $app->last_name,
                'name'                 => trim(($user?->first_name ?? $app->first_name) . ' ' . ($user?->last_name ?? $app->last_name)),
                'email'                => $user?->email ?? $app->email,
                'avatar'               => $user?->avatar_url ?? null,
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

        // Summary stats for this company
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
     * 11. View Single Intern Detailed Performance (Company-Scoped)
     * GET /api/company/performance/{internId}
     */
    public function internPerformanceDetail(Request $request, $internId)
    {
        $companyId = $this->getCompanyId($request);
        $internshipIds = $this->getCompanyInternshipIds($companyId);

        // Security check: intern must be officially approved for this company's internships
        $app = InternshipApplication::with(['user', 'internship'])
            ->whereIn('internship_id', $internshipIds)
            ->where('status', 'approved')
            ->where(function($q) use ($internId) {
                $q->where('user_id', $internId)->orWhere('id', $internId);
            })
            ->firstOrFail();

        $userId = $app->user_id ?? $app->user?->id;

        $tasks = InternshipTask::with(['latestSubmission.reviewer', 'submissions.reviewer', 'assignedBy'])
            ->where('company_id', $companyId)
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
