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
        $query = ExpertProfile::whereHas('user', function($q) {
            $q->whereNull('deleted_at');
        })->with(['user'])->latest();

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
        ])->where(function ($q) use ($instructorId) {
            $q->where('user_id', $instructorId)->orWhere('id', $instructorId);
        })->firstOrFail();
    }

    public function createInstructor(array $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $firstName = trim($data['first_name'] ?? 'Instructor');
            $lastName = trim($data['last_name'] ?? '');
            $fullName = trim($firstName . ' ' . $lastName) ?: 'Instructor';

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName,
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make($data['password'] ?? 'Password@123'),
                'status' => 'active'
            ]);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'expert', 'guard_name' => 'web']);
            $user->assignRole($role);

            $profileData = collect($data)->except(['first_name', 'last_name', 'email', 'phone', 'password', 'avatar', 'avatarFile'])->toArray();

            // Handle file upload or base64 avatar
            $photoUrl = null;
            if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $data['avatar']->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } elseif (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $data['profile_photo']->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } else {
                $avatarStr = is_string($data['avatar'] ?? null) ? $data['avatar'] : (is_string($data['profile_photo'] ?? null) ? $data['profile_photo'] : null);
                if ($avatarStr && strpos($avatarStr, 'data:image') === 0) {
                    $imageParts = explode(';base64,', $avatarStr);
                    if (count($imageParts) === 2) {
                        $imageType = explode('/', $imageParts[0])[1] ?? 'png';
                        if (str_contains($imageType, ';')) $imageType = explode(';', $imageType)[0];
                        $imageDecoded = base64_decode($imageParts[1]);
                        $fileName = 'avatars/' . uniqid() . '.' . $imageType;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageDecoded);
                        $photoUrl = '/storage/' . $fileName;
                    }
                } elseif ($avatarStr && (str_starts_with($avatarStr, 'http') || str_starts_with($avatarStr, '/'))) {
                    $photoUrl = $avatarStr;
                }
            }

            if ($photoUrl) {
                $profileData['profile_photo'] = $photoUrl;
            }

            $profileData['designation'] = $data['designation'] ?? 'Expert';
            $profileData['company'] = $data['company'] ?? 'Independent';
            $profileData['specialization'] = $data['specialization'] ?? 'Career & Technical Mentorship';
            $profileData['hourly_rate'] = !empty($data['hourly_rate']) ? (float)$data['hourly_rate'] : 1500.0;
            $profileData['approval_status'] = 'approved';
            $profileData['is_verified'] = true;
            $profileData['is_available'] = true;
            $profileData['average_rating'] = 5.0;
            $profileData['total_reviews'] = 0;
            
            $user->expertProfile()->create($profileData);

            \Illuminate\Support\Facades\Cache::flush();

            return $user;
        });
    }

    public function updateInstructor(int $instructorId, array $data)
    {
        $profile = ExpertProfile::where('user_id', $instructorId)->orWhere('id', $instructorId)->firstOrFail();
        
        // Update user basics if present
        if ($profile->user) {
            $userUpdates = [];
            if (isset($data['first_name'])) $userUpdates['first_name'] = trim($data['first_name']);
            if (isset($data['last_name'])) $userUpdates['last_name'] = trim($data['last_name']);
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $firstName = $userUpdates['first_name'] ?? $profile->user->first_name;
                $lastName = $userUpdates['last_name'] ?? $profile->user->last_name;
                $userUpdates['name'] = trim($firstName . ' ' . $lastName);
            }
            if (isset($data['email']) && !empty($data['email'])) {
                $userUpdates['email'] = strtolower(trim($data['email']));
            }
            if (isset($data['phone'])) {
                $userUpdates['phone'] = trim($data['phone']);
            }
            if (!empty($userUpdates)) {
                $profile->user->update($userUpdates);
            }
        }
        
        $profileData = collect($data)->only((new ExpertProfile)->getFillable())->except(['user_id'])->toArray();

        // Handle file upload or base64 avatar
        $photoUrl = null;
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['avatar']->store('avatars', 'public');
            $photoUrl = '/storage/' . $path;
        } elseif (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['profile_photo']->store('avatars', 'public');
            $photoUrl = '/storage/' . $path;
        } else {
            $avatarStr = is_string($data['avatar'] ?? null) ? $data['avatar'] : (is_string($data['profile_photo'] ?? null) ? $data['profile_photo'] : null);
            if ($avatarStr && strpos($avatarStr, 'data:image') === 0) {
                $imageParts = explode(';base64,', $avatarStr);
                if (count($imageParts) === 2) {
                    $imageType = explode('/', $imageParts[0])[1] ?? 'png';
                    if (str_contains($imageType, ';')) $imageType = explode(';', $imageType)[0];
                    $imageDecoded = base64_decode($imageParts[1]);
                    $fileName = 'avatars/' . uniqid() . '.' . $imageType;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageDecoded);
                    $photoUrl = '/storage/' . $fileName;
                }
            } elseif ($avatarStr && (str_starts_with($avatarStr, 'http') || str_starts_with($avatarStr, '/'))) {
                $photoUrl = $avatarStr;
            }
        }

        if ($photoUrl) {
            $profileData['profile_photo'] = $photoUrl;
        }

        if (isset($data['hourly_rate'])) {
            $profileData['hourly_rate'] = (float)$data['hourly_rate'];
        }

        $profile->update($profileData);

        \Illuminate\Support\Facades\Cache::flush();

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
