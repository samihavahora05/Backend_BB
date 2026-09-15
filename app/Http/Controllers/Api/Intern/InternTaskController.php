<?php

namespace App\Http\Controllers\Api\Intern;

use App\Http\Controllers\Controller;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InternTaskController extends Controller
{
    private function getInternId(Request $request): int
    {
        return $request->user()->id;
    }

    /**
     * 1. List Tasks for the Logged-In Intern
     * GET /api/intern/tasks
     */
    public function index(Request $request)
    {
        $this->ensureSchemaIntegrity();
        $internId = $this->getInternId($request);

        $query = InternshipTask::with([
            'company:id,first_name,last_name,name,email',
            'internship:id,title,category,company_name,location',
            'latestSubmission',
        ])->where('assigned_to', $internId);

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
                  ->orWhereHas('internship', fn($iq) => $iq->where('title', 'like', "%{$search}%"));
            });
        }

                $tasks = $query->orderBy('due_date', 'asc')->paginate($request->input('per_page', 20));

        // Synchronize and reflect latest review status for each task
        $tasks->through(function ($task) {
            if ($task->latestSubmission) {
                if ($task->latestSubmission->status === 'resubmit' || $task->latestSubmission->status === 'rejected') {
                    if ($task->status !== 'changes_required') {
                        $task->status = 'changes_required';
                        $task->feedback = $task->latestSubmission->feedback ?? $task->feedback;
                        InternshipTask::where('id', $task->id)->update([
                            'status'   => 'changes_required',
                            'feedback' => $task->feedback,
                        ]);
                    }
                } elseif ($task->latestSubmission->status === 'approved') {
                    if ($task->status !== 'completed' && $task->status !== 'approved') {
                        $task->status = 'completed';
                        $task->marks = $task->latestSubmission->marks_obtained ?? $task->marks ?? 100;
                        $task->feedback = $task->latestSubmission->feedback ?? $task->feedback;
                        InternshipTask::where('id', $task->id)->update([
                            'status'       => 'completed',
                            'marks'        => $task->marks,
                            'feedback'     => $task->feedback,
                            'completed_at' => $task->completed_at ?? now(),
                        ]);
                    }
                }
            }
            return $task;
        });

        $allTasks = InternshipTask::where('assigned_to', $internId)->get();

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
     * 2. View Single Task Detail
     * GET /api/intern/tasks/{id}
     */
    public function show(Request $request, $id)
    {
        $internId = $this->getInternId($request);

        $task = InternshipTask::with([
            'company:id,first_name,last_name,name,email',
            'internship:id,title,category,company_name,location,start_date,end_date',
            'submissions.reviewer:id,first_name,last_name',
        ])->where('assigned_to', $internId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $task,
        ]);
    }

    /**
     * 3. Start Task (Transition to in_progress)
     * POST /api/intern/tasks/{id}/start
     */
    public function start(Request $request, $id)
    {
        $internId = $this->getInternId($request);
        $task = InternshipTask::where('assigned_to', $internId)->findOrFail($id);

        if ($task->status === 'assigned') {
            $task->update([
                'status'     => 'in_progress',
                'start_date' => $task->start_date ?? now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task marked as In Progress.',
            'data'    => $task,
        ]);
    }

    /**
     * Helper to guarantee schema integrity for internship submissions (drop legacy unique constraint for multi-version submissions)
     */
    private function ensureSchemaIntegrity(): void
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM internship_submissions WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
                $dropped = [];
                foreach ($indexes as $index) {
                    $keyName = $index->Key_name ?? $index->key_name ?? null;
                    if ($keyName && !in_array($keyName, $dropped) && (str_contains(strtolower($keyName), 'task_id') || str_contains(strtolower($keyName), 'user_id'))) {
                        DB::statement("ALTER TABLE `internship_submissions` DROP INDEX `{$keyName}`");
                        $dropped[] = $keyName;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist or already dropped
        }
    }

    /**
     * 4. Submit Task Deliverables & Mandatory Proof
     * POST /api/intern/tasks/{id}/submit
     */
    public function submit(Request $request, $id)
    {
        $this->ensureSchemaIntegrity();
        $internId = $this->getInternId($request);
        $task = InternshipTask::where('assigned_to', $internId)->findOrFail($id);

        $validated = $request->validate([
            'submission_comment' => 'required|string|max:3000',
            'proof_files'        => 'nullable|array',
            'proof_files.*'      => 'nullable|string',
            'file'               => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,zip|max:20480',
            'github_link'        => 'nullable|url|max:255',
            'video_link'         => 'nullable|url|max:255',
        ]);

        $uploadedProofFiles = $validated['proof_files'] ?? [];

        // Handle single or multi-part file upload if attached
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('submissions/' . $task->id, 'public');
            $uploadedProofFiles[] = Storage::disk('public')->url($path);
        }

        // Proof upload is MANDATORY per business requirements
        if (empty($uploadedProofFiles) && empty($validated['github_link']) && empty($validated['video_link'])) {
            return response()->json([
                'success' => false,
                'message' => 'Proof deliverable is mandatory. Please upload your proof file or project link.',
            ], 422);
        }

        $latestVersion = InternshipSubmission::where('task_id', $task->id)->max('version') ?? 0;
        $nextVersion = $latestVersion + 1;

        $submissionData = [
            'task_id'            => $task->id,
            'user_id'            => $internId,
            'version'            => $nextVersion,
            'submission_text'    => $validated['submission_comment'],
            'submission_comment' => $validated['submission_comment'],
            'proof_files'        => $uploadedProofFiles,
            'file_paths'         => $uploadedProofFiles,
            'github_link'        => $validated['github_link'] ?? null,
            'video_link'         => $validated['video_link'] ?? null,
            'status'             => 'pending',
        ];

        try {
            $submission = InternshipSubmission::create($submissionData);
        } catch (\Throwable $e) {
            // Self-heal: Drop unique constraint and retry, or fallback to update
            $this->ensureSchemaIntegrity();
            try {
                $submission = InternshipSubmission::create($submissionData);
            } catch (\Throwable $ex) {
                $submission = InternshipSubmission::where('task_id', $task->id)->where('user_id', $internId)->latest()->first();
                if ($submission) {
                    $submission->update($submissionData);
                } else {
                    throw $e;
                }
            }
        }

        $task->update([
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task deliverable submitted successfully and is now under review.',
            'data'    => [
                'task'       => $task->fresh(['latestSubmission']),
                'submission' => $submission,
            ]
        ]);
    }

    /**
     * 5. Intern Performance Overview & Evaluated Task History
     * GET /api/intern/tasks/performance
     */
    public function performance(Request $request)
    {
        $internId = $this->getInternId($request);

        $allTasks = InternshipTask::with([
            'company:id,first_name,last_name,name,email',
            'internship:id,title,category,company_name',
            'latestSubmission',
        ])->where('assigned_to', $internId)->get();

        // Performance Rate is calculated ONLY from approved/completed tasks with awarded marks
        $approvedTasks = $allTasks->filter(function ($t) {
            return in_array($t->status, ['completed', 'approved']) && !is_null($t->marks);
        });

        $totalMarksObtained = (float) $approvedTasks->sum('marks');
        $totalMaxMarks = (float) $approvedTasks->sum(fn($t) => $t->max_marks ?: 100);

        $performanceRate = $totalMaxMarks > 0 
            ? round(($totalMarksObtained / $totalMaxMarks) * 100, 1) 
            : 0.0;

        $averageMarks = $approvedTasks->count() > 0 
            ? round($approvedTasks->avg('marks'), 1) 
            : 0.0;

        $stats = [
            'performance_rate'       => $performanceRate,
            'average_marks'          => $averageMarks,
            'total_tasks'            => $allTasks->count(),
            'completed_tasks'        => $approvedTasks->count(),
            'pending_review_tasks'   => $allTasks->whereIn('status', ['submitted', 'under_review'])->count(),
            'revision_required_tasks'=> $allTasks->where('status', 'changes_required')->count(),
            'in_progress_tasks'      => $allTasks->where('status', 'in_progress')->count(),
            'assigned_tasks'         => $allTasks->where('status', 'assigned')->count(),
            'total_marks_obtained'   => $totalMarksObtained,
            'total_max_marks'        => $totalMaxMarks,
        ];

        // Format evaluated tasks history
        $evaluatedHistory = $approvedTasks->values()->map(function ($task) {
            return [
                'id'             => $task->id,
                'title'          => $task->title,
                'priority'       => $task->priority,
                'marks'          => (float) $task->marks,
                'max_marks'      => (float) ($task->max_marks ?: 100),
                'percentage'     => ($task->max_marks ?: 100) > 0 ? round(($task->marks / ($task->max_marks ?: 100)) * 100, 1) : 0,
                'feedback'       => $task->feedback,
                'completed_at'   => $task->completed_at ?? $task->updated_at,
                'company_name'   => $task->company->name ?? (($task->company->first_name ?? '') . ' ' . ($task->company->last_name ?? '')) ?: 'System Admin',
                'internship_title'=> $task->internship->title ?? 'General Internship',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'             => $stats,
                'evaluated_history' => $evaluatedHistory,
            ]
        ]);
    }
}