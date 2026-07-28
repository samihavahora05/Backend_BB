<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;

class AdminJobInterviewController extends Controller
{
    protected $service;

    public function __construct(JobApplicationService $service)
    {
        $this->service = $service;
    }

    public function schedule(Request $request, $applicationId)
    {
        $this->authorize('manage jobs');
        $interview = $this->service->scheduleInterview($applicationId, $request->all(), $request->user()->id);
        return response()->json(['success' => true, 'data' => $interview], 201);
    }

    public function grade(Request $request, $interviewId)
    {
        $this->authorize('manage jobs');
        $interview = $this->service->gradeInterview($interviewId, $request->all(), $request->user()->id);
        return response()->json(['success' => true, 'data' => $interview]);
    }
}
