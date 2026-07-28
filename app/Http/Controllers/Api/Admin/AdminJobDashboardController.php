<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\JobRepositoryInterface;

class AdminJobDashboardController extends Controller
{
    protected $repository;

    public function __construct(JobRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getMetrics()
    {
        $this->authorize('manage jobs');
        $metrics = $this->repository->getDashboardMetrics();
        return response()->json(['success' => true, 'data' => $metrics]);
    }
}
