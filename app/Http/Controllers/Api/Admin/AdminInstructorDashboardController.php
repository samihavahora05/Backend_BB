<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Http\Request;

class AdminInstructorDashboardController extends Controller
{
    protected $repository;

    public function __construct(InstructorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getMetrics(Request $request)
    {
        $this->authorize('manage experts');
        $metrics = $this->repository->getDashboardMetrics();
        return response()->json(['success' => true, 'data' => $metrics]);
    }

    public function getInstructorMetrics(Request $request, $id)
    {
        $this->authorize('manage experts');
        $metrics = $this->repository->getInstructorMetrics($id);
        return response()->json(['success' => true, 'data' => $metrics]);
    }
}
