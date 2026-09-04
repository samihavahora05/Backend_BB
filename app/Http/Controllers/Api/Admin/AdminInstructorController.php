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
            $profile = \App\Models\ExpertProfile::where('id', $id)
                ->orWhere('user_id', $id)
                ->first();
            $userId = $profile ? $profile->user_id : (int)$id;
            $profileId = $profile ? $profile->id : (int)$id;

            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

            \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $profileId) {
                // 1. Delete linked expert records
                try { \Illuminate\Support\Facades\DB::table('expert_availabilities')->where('expert_profile_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_bookings')->where('expert_profile_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_course_assignments')->where('expert_id', $userId)->orWhere('expert_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_activity_logs')->where('expert_id', $userId)->orWhere('admin_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_reviews')->where('expert_id', $userId)->orWhere('student_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_certificates')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_languages')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_documents')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('expert_skills')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('mentor_sessions')->where('expert_id', $profileId)->orWhere('expert_profile_id', $profileId)->orWhere('expert_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { \Illuminate\Support\Facades\DB::table('mentor_bookings')->where('expert_id', $profileId)->orWhere('expert_id', $userId)->delete(); } catch (\Throwable $t) {}
                
                // 2. Reassign any assigned courses to super admin
                try {
                    $adminUser = \App\Models\User::role('super_admin')->first() ?? \App\Models\User::role('admin')->first();
                    if ($adminUser) {
                        \Illuminate\Support\Facades\DB::table('courses')->where('expert_id', $userId)->orWhere('expert_id', $profileId)->update(['expert_id' => $adminUser->id]);
                    }
                } catch (\Throwable $t) {}

                // 3. Delete ExpertProfile record
                try {
                    \Illuminate\Support\Facades\DB::table('expert_profiles')->where('id', $profileId)->orWhere('user_id', $userId)->delete();
                } catch (\Throwable $t) {}

                // 4. Detach roles & force delete User
                $user = \App\Models\User::withTrashed()->where('id', $userId)->first();
                if ($user) {
                    try {
                        if (method_exists($user, 'roles')) {
                            $user->roles()->detach();
                        }
                    } catch (\Throwable $t) {}
                    try {
                        $user->forceDelete();
                    } catch (\Throwable $t) {
                        \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->delete();
                    }
                }
            });

            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

            // 5. Clear all cache
            \Illuminate\Support\Facades\Cache::flush();
            return response()->json(['success' => true, 'message' => 'Instructor deleted permanently']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            \Illuminate\Support\Facades\Log::error('Instructor delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete instructor: ' . $e->getMessage()], 500);
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
