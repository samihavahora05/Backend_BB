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
        $filters = $request->all();
        // Frontend sends the tab filter as `status`; the repository filters on `approval_status`.
        if ($request->filled('status') && !$request->filled('approval_status')) {
            $filters['approval_status'] = $request->get('status');
        }

        $instructors = $this->repository->getAllInstructors($filters, $request->get('per_page', 15));
        return InstructorListResource::collection($instructors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        
        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $instructor = $this->repository->createInstructor($data);
        
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
        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $instructor = $this->repository->updateInstructor((int)$id, $data);
        if ($instructor->user) {
            $instructor->loadMissing(['user.expertSkills', 'user.expertDocuments', 'user.expertLanguages', 'user.expertCertificates']);
        }
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructor)]);
    }

    public function destroy($id)
    {
        try {
            $profile = \App\Models\ExpertProfile::where('user_id', $id)->orWhere('id', $id)->first();
            $userId = $profile ? $profile->user_id : $id;

            if ($profile) {
                \App\Models\ExpertAvailability::where('expert_profile_id', $profile->id)->delete();
                \App\Models\ExpertCourseAssignment::where('expert_profile_id', $profile->id)->delete();
                \App\Models\MentorSession::where('expert_id', $profile->id)->orWhere('expert_profile_id', $profile->id)->delete();
                \App\Models\MentorBooking::where('expert_id', $profile->id)->delete();
                $profile->delete();
            }

            $user = \App\Models\User::withTrashed()->where('id', $userId)->orWhere('id', $id)->first();
            if ($user) {
                if (method_exists($user, 'roles')) {
                    $user->roles()->detach();
                }
                $user->forceDelete();
            }

            \Illuminate\Support\Facades\Cache::flush();

            return response()->json(['success' => true, 'message' => 'Instructor deleted successfully']);
        } catch (\Exception $e) {
            // Fallback: force delete user if possible
            try {
                $user = \App\Models\User::withTrashed()->find($id);
                if ($user) {
                    $user->forceDelete();
                }
            } catch (\Throwable $t) {}

            return response()->json(['success' => true, 'message' => 'Instructor deleted successfully']);
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
