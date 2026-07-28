<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentDashboardResource;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Http\Request;

class AdminStudentDashboardController extends Controller
{
    protected $repository;

    public function __construct(StudentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAggregatedData($id)
    {
        $this->authorize('manage students');
        
        $metrics = $this->repository->getStudentDashboardMetrics($id);
        $courses = $this->repository->getStudentPurchasedCourses($id);
        $internships = $this->repository->getStudentInternships($id);
        $jobs = $this->repository->getStudentJobs($id);
        $attendance = $this->repository->getStudentAttendance($id);

        return new StudentDashboardResource([
            'metrics' => $metrics,
            'courses' => $courses,
            'internships' => $internships,
            'jobs' => $jobs,
            'attendance' => $attendance,
        ]);
    }
}
