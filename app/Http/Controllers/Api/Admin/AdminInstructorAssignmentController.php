<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\InstructorService;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Http\Request;

class AdminInstructorAssignmentController extends Controller
{
    protected $service;
    protected $repository;

    public function __construct(InstructorService $service, InstructorRepositoryInterface $repository)
    {
        $this->service = $service;
        $this->repository = $repository;
    }

    public function getCourses(Request $request, $id)
    {
        $this->authorize('manage experts');
        $courses = $this->repository->getInstructorCourses($id, $request->all(), $request->get('per_page', 15));
        return response()->json($courses);
    }

    public function assignCourse(Request $request, $id)
    {
        $this->authorize('manage experts');
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'role' => 'nullable|string'
        ]);

        $assignment = $this->service->assignCourse(
            $id, 
            $request->course_id, 
            $request->role ?? 'Primary Instructor', 
            $request->user()->id
        );

        return response()->json(['success' => true, 'data' => $assignment]);
    }
}
