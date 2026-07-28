<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentListResource;
use App\Http\Resources\StudentDetailResource;
use App\Repositories\Contracts\StudentRepositoryInterface;
use App\Services\StudentService;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    protected $repository;
    protected $service;

    public function __construct(StudentRepositoryInterface $repository, StudentService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $this->authorize('manage students');
        $students = $this->repository->getAllStudents($request->all(), $request->get('per_page', 15));
        return StudentListResource::collection($students);
    }

    public function store(StoreStudentRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('students/avatars', 'public');
        }
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume')->store('students/resumes', 'public');
        }

        $student = $this->repository->createStudent($data);
        return response()->json(['success' => true, 'data' => new StudentDetailResource($student)], 201);
    }

    public function show($id)
    {
        $this->authorize('manage students');
        $student = $this->repository->getStudentById($id);
        return response()->json(['success' => true, 'data' => new StudentDetailResource($student)]);
    }

    public function update(UpdateStudentRequest $request, $id)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('students/avatars', 'public');
        }
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume')->store('students/resumes', 'public');
        }

        $student = $this->repository->updateStudent($id, $data);
        return response()->json(['success' => true, 'data' => new StudentDetailResource($student)]);
    }

    public function destroy($id)
    {
        $this->authorize('manage students');
        $this->repository->deleteStudent($id);
        return response()->json(['success' => true, 'message' => 'Student deleted successfully']);
    }

    public function suspend(Request $request, $id)
    {
        $this->authorize('manage students');
        $this->service->toggleStatus($id, 'suspended', $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Student suspended']);
    }

    public function activate(Request $request, $id)
    {
        $this->authorize('manage students');
        $this->service->toggleStatus($id, 'active', $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Student activated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('manage students');
        $ids = $request->input('ids', []);
        foreach ($ids as $id) {
            $this->repository->deleteStudent($id);
        }
        return response()->json(['success' => true, 'message' => count($ids) . ' students deleted']);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $this->authorize('manage students');
        $ids = $request->input('ids', []);
        $status = $request->input('status'); // active, suspended, archived
        foreach ($ids as $id) {
            $this->service->toggleStatus($id, $status, $request->user()->id);
        }
        return response()->json(['success' => true, 'message' => count($ids) . ' students marked as ' . $status]);
    }

    public function export(Request $request)
    {
        $this->authorize('manage students');
        $students = $this->repository->getAllStudents($request->all(), 10000);

        $headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'DOB', 'Gender', 'Student Type', 'College/Company', 'Course/Role', 'City', 'Country', 'Status', 'Joined At'];
        
        $callback = function() use ($students, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($students as $student) {
                fputcsv($file, [
                    $student->id,
                    $student->first_name,
                    $student->last_name,
                    $student->email,
                    $student->phone,
                    $student->studentProfile?->date_of_birth?->format('Y-m-d'),
                    $student->studentProfile?->gender,
                    $student->studentProfile?->student_type,
                    $student->studentProfile?->college_name ?? $student->studentProfile?->company_name,
                    $student->studentProfile?->course ?? $student->studentProfile?->job_title,
                    $student->studentProfile?->city,
                    $student->studentProfile?->country,
                    $student->status,
                    $student->created_at?->format('Y-m-d'),
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'students_export_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
