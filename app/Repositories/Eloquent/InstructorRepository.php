<?php

namespace App\Repositories\Eloquent;

use App\Models\ExpertProfile;
use App\Models\ExpertCourseAssignment;
use App\Models\User;
use App\Repositories\Contracts\InstructorRepositoryInterface;

class InstructorRepository implements InstructorRepositoryInterface
{
    public function getAllInstructors(array $filters = [], int $perPage = 15)
    {
        $query = ExpertProfile::with(['user'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }
        
        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        return $query->paginate($perPage);
    }

    public function getInstructorById(int $instructorId)
    {
        return ExpertProfile::with([
            'user.expertSkills', 
            'user.expertDocuments', 
            'user.expertLanguages', 
            'user.expertCertificates'
        ])->where('user_id', $instructorId)->firstOrFail();
    }

    public function createInstructor(array $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'] ?? 'Instructor',
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'status' => 'active'
            ]);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'expert', 'guard_name' => 'web']);
            $user->assignRole($role);

            $profileData = collect($data)->except(['first_name', 'last_name', 'email', 'phone', 'password', 'avatar'])->toArray();

            if (!empty($data['avatar']) && strpos($data['avatar'], 'data:image') === 0) {
                // handle base64 image
                $imageParts = explode(';base64,', $data['avatar']);
                if (count($imageParts) === 2) {
                    $imageType = explode('/', $imageParts[0])[1];
                    $imageDecoded = base64_decode($imageParts[1]);
                    $fileName = 'avatars/' . uniqid() . '.' . $imageType;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageDecoded);
                    $profileData['profile_photo'] = '/storage/' . $fileName;
                }
            }

            $profileData['approval_status'] = 'approved';
            $profileData['is_verified'] = true;
            
            $user->expertProfile()->create($profileData);

            return $user;
        });
    }

    public function updateInstructor(int $instructorId, array $data)
    {
        $profile = ExpertProfile::where('user_id', $instructorId)->firstOrFail();
        
        // Update user basics if present
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $profile->user->update([
                'first_name' => $data['first_name'] ?? $profile->user->first_name,
                'last_name' => $data['last_name'] ?? $profile->user->last_name,
            ]);
        }
        
        $profile->update($data);
        return $profile;
    }

    public function getInstructorCourses(int $instructorId, array $filters = [], int $perPage = 15)
    {
        return ExpertCourseAssignment::with(['course'])
            ->where('expert_id', $instructorId)
            ->paginate($perPage);
    }

    public function getDashboardMetrics()
    {
        return [
            'total_instructors' => ExpertProfile::count(),
            'pending_approval' => ExpertProfile::where('approval_status', 'pending')->count(),
            'approved_instructors' => ExpertProfile::where('approval_status', 'approved')->count(),
            'suspended_instructors' => ExpertProfile::where('approval_status', 'suspended')->count(),
        ];
    }
    
    public function getInstructorMetrics(int $instructorId)
    {
        $profile = ExpertProfile::where('user_id', $instructorId)->firstOrFail();
        
        return [
            'total_revenue' => $profile->total_revenue,
            'average_rating' => $profile->average_rating,
            'total_courses_sold' => $profile->total_courses_sold,
            'total_students' => $profile->total_students,
            'completion_rate' => $profile->completion_rate,
            'student_satisfaction' => $profile->student_satisfaction,
            'total_certificates_issued' => $profile->total_certificates_issued,
        ];
    }
}
