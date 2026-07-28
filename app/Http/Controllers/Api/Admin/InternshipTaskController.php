<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternshipTaskRequest;
use App\Repositories\Contracts\InternshipRepositoryInterface;
use Illuminate\Http\Request;

class InternshipTaskController extends Controller
{
    protected $repository;

    public function __construct(InternshipRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index($internshipId)
    {
        $this->authorize('manage internships');
        $tasks = $this->repository->getTasksForInternship($internshipId);
        
        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    public function store(InternshipTaskRequest $request)
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user()->id;
        
        $task = $this->repository->createTask($data);
        return response()->json(['success' => true, 'data' => $task], 201);
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $this->authorize('manage internships');
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,resubmit',
            'marks_obtained' => 'nullable|numeric|min:0',
            'feedback' => 'nullable|string'
        ]);

        $submission = $this->repository->gradeSubmission($submissionId, $request->only('status', 'marks_obtained', 'feedback'));
        
        return response()->json(['success' => true, 'data' => $submission]);
    }
}
