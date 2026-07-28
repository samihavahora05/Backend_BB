<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Repositories\Contracts\InternshipRepositoryInterface;
use App\Services\InternshipApplicationService;
use Illuminate\Http\Request;

class InternshipApplicationController extends Controller
{
    protected $repository;
    protected $service;

    public function __construct(InternshipRepositoryInterface $repository, InternshipApplicationService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request, $internshipId)
    {
        $this->authorize('manage internships');
        $applications = $this->repository->getApplicationsForInternship($internshipId, $request->all(), $request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    public function show($id)
    {
        $this->authorize('manage internships');
        $application = $this->repository->getApplicationById($id);
        return response()->json(['success' => true, 'data' => $application]);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, $id)
    {
        $application = $this->service->processStatusUpdate(
            $id, 
            $request->status, 
            $request->user()->id,
            $request->internal_notes
        );
        
        return response()->json(['success' => true, 'data' => $application, 'message' => 'Status updated successfully']);
    }

    public function allApplications(Request $request)
    {
        $this->authorize('manage internships');
        $applications = $this->repository->getAllApplications($request->all(), $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    public function allSubmissions(Request $request)
    {
        $this->authorize('manage internships');
        $submissions = $this->repository->getAllSubmissions($request->all(), $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $submissions
        ]);
    }

    public function assignTask(Request $request, $id)
    {
        $this->authorize('manage internships');
        $this->service->assignTask($id, $request->task_id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully'
        ]);
    }
}
