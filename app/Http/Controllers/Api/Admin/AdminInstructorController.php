<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Http\Resources\InstructorListResource;
use App\Http\Resources\InstructorDetailResource;
use Illuminate\Http\Request;

class AdminInstructorController extends Controller
{
    protected $repository;

    public function __construct(InstructorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $instructors = $this->repository->getAllInstructors($request->all(), $request->get('per_page', 15));
        return InstructorListResource::collection($instructors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        
        $instructor = $this->repository->createInstructor($request->all());
        
        $instructorWithProfile = $this->repository->getInstructorById($instructor->id);
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructorWithProfile)], 201);
    }

    public function show($id)
    {
        $instructor = $this->repository->getInstructorById($id);
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructor)]);
    }

    public function update(Request $request, $id)
    {
        $instructor = $this->repository->updateInstructor($id, $request->all());
        $instructor = $this->repository->getInstructorById($id);
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructor)]);
    }

    public function destroy($id)
    {
        try {
            $profile = \App\Models\ExpertProfile::where('user_id', $id)->firstOrFail();
            
            // Delete the profile
            $profile->delete();
            
            // Try to delete the user if it exists
            $user = \App\Models\User::find($id);
            if ($user) {
                $user->delete(); // soft-deletes the user
            }
            
            return response()->json(['success' => true, 'message' => 'Instructor deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Profile not found, but maybe user exists? Let's clean up user too just in case
            $user = \App\Models\User::find($id);
            if ($user && $user->hasRole('expert')) {
                $user->delete();
                return response()->json(['success' => true, 'message' => 'Instructor deleted successfully']);
            }
            return response()->json(['success' => false, 'message' => 'Instructor not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $instructors = $this->repository->getAllInstructors($request->all(), 10000);

        $headers = ['User ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Specialization', 'Approval Status', 'Avg Rating', 'Joined At'];
        $rows = [];
        foreach ($instructors as $profile) {
            $user = $profile->user;
            $rows[] = [
                $user?->id,
                $user?->first_name,
                $user?->last_name,
                $user?->email,
                $user?->phone,
                $profile->specialization,
                $profile->approval_status,
                $profile->average_rating,
                $user?->created_at?->format('Y-m-d'),
            ];
        }

        $export = new class($headers, $rows) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $headers;
            private $rows;
            public function __construct($headers, $rows) {
                $this->headers = $headers;
                $this->rows = $rows;
            }
            public function array(): array {
                return array_merge([$this->headers], $this->rows);
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'instructors_export_' . now()->format('Y-m-d') . '.xlsx');
    }
}
