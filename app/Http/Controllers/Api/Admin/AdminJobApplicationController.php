<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\JobRepositoryInterface;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;

class AdminJobApplicationController extends Controller
{
    protected $repository;
    protected $service;

    public function __construct(JobRepositoryInterface $repository, JobApplicationService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request, $jobId)
    {
        $this->authorize('manage jobs');
        $applications = $this->repository->getJobApplications($jobId, $request->all(), $request->get('per_page', 15));
        return response()->json($applications);
    }

    public function show($id)
    {
        $this->authorize('manage jobs');
        $application = $this->repository->getApplicationById($id);
        return response()->json(['success' => true, 'data' => $application]);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorize('manage jobs');
        $request->validate(['status' => 'required|string', 'notes' => 'nullable|string']);
        
        $application = $this->service->updateApplicationStatus(
            $id, 
            $request->status, 
            $request->user()->id, 
            $request->notes
        );
        
        return response()->json(['success' => true, 'data' => $application]);
    }
}
