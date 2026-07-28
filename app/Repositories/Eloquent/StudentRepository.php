<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\CourseEnrollment;
use App\Models\InternshipApplication;
use App\Models\JobApplication;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentRepository implements StudentRepositoryInterface
{
    public function getAllStudents(array $filters = [], int $perPage = 15)
    {
        $query = User::role('student')->with(['studentProfile']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getStudentById(int $userId)
    {
        return User::role('student')
            ->with([
                'studentProfile',
                'education',
                'skills',
                'socialLinks',
                'documents',
                'preferences'
            ])
            ->findOrFail($userId);
    }

    public function createStudent(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active'
            ]);

            $user->assignRole('student');

            $profileData = collect($data)->only([
                'bio', 'date_of_birth', 'gender', 'student_type', 'job_title', 
                'company_name', 'identification_number', 'pin', 'address_line_1', 
                'address_line_2', 'city', 'state', 'country', 'profile_photo'
            ])->toArray();
            
            $user->studentProfile()->create($profileData);

            // Education
            if (isset($data['course']) || isset($data['college_name'])) {
                $user->education()->create([
                    'course' => $data['course'] ?? null,
                    'specialization' => $data['department'] ?? null,
                    'semester' => $data['semester'] ?? null,
                    'college_name' => $data['college_name'] ?? null,
                ]);
            }

            // Social Links
            $platforms = ['github' => 'github_url', 'linkedin' => 'linkedin_url', 'portfolio' => 'portfolio_url'];
            foreach ($platforms as $platform => $key) {
                if (!empty($data[$key])) {
                    $user->socialLinks()->create([
                        'platform' => $platform,
                        'url' => $data[$key]
                    ]);
                }
            }

            // Skills
            if (!empty($data['skills'])) {
                $skills = is_string($data['skills']) ? json_decode($data['skills'], true) : $data['skills'];
                if (is_array($skills)) {
                    foreach ($skills as $skill) {
                        $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill_name'] ?? '') : $skill;
                        if (!empty($skillName)) {
                            $user->skills()->create(['skill_name' => $skillName]);
                        }
                    }
                }
            }
            
            // Resume/Document
            if (isset($data['resume'])) {
                $user->documents()->create([
                    'type' => 'resume',
                    'title' => 'Resume',
                    'file_path' => $data['resume']
                ]);
            }

            return $user;
        });
    }

    public function updateStudent(int $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = User::findOrFail($userId);
            
            $userData = collect($data)->only(['first_name', 'last_name', 'email', 'phone', 'status'])->toArray();
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            $user->update($userData);

            $profileData = collect($data)->only([
                'bio', 'date_of_birth', 'gender', 'student_type', 'job_title', 
                'company_name', 'identification_number', 'pin', 'address_line_1', 
                'address_line_2', 'city', 'state', 'country', 'profile_photo'
            ])->toArray();
            
            if (!empty($profileData)) {
                $user->studentProfile()->updateOrCreate(['user_id' => $user->id], $profileData);
            }

            // Education
            if (isset($data['course']) || isset($data['college_name'])) {
                $user->education()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'course' => $data['course'] ?? null,
                        'specialization' => $data['department'] ?? null,
                        'semester' => $data['semester'] ?? null,
                        'college_name' => $data['college_name'] ?? null,
                    ]
                );
            }

            // Social Links
            $platforms = ['github' => 'github_url', 'linkedin' => 'linkedin_url', 'portfolio' => 'portfolio_url'];
            foreach ($platforms as $platform => $key) {
                if (array_key_exists($key, $data)) {
                    if (empty($data[$key])) {
                        $user->socialLinks()->where('platform', $platform)->delete();
                    } else {
                        $user->socialLinks()->updateOrCreate(
                            ['user_id' => $user->id, 'platform' => $platform],
                            ['url' => $data[$key]]
                        );
                    }
                }
            }

            // Skills
            if (array_key_exists('skills', $data)) {
                $user->skills()->delete(); // Clear existing
                if (!empty($data['skills'])) {
                    $skills = is_string($data['skills']) ? json_decode($data['skills'], true) : $data['skills'];
                    if (is_array($skills)) {
                        foreach ($skills as $skill) {
                            $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill_name'] ?? '') : $skill;
                            if (!empty($skillName)) {
                                $user->skills()->create(['skill_name' => $skillName]);
                            }
                        }
                    }
                }
            }

            // Resume/Document
            if (isset($data['resume'])) {
                $user->documents()->updateOrCreate(
                    ['user_id' => $user->id, 'type' => 'resume'],
                    ['title' => 'Resume', 'file_path' => $data['resume']]
                );
            }

            return $user;
        });
    }

    public function deleteStudent(int $userId)
    {
        $user = User::findOrFail($userId);
        return $user->delete();
    }

    // --- Complex Dashboard Aggregations ---

    public function getStudentDashboardMetrics(int $userId)
    {
        return [
            'total_courses' => CourseEnrollment::where('user_id', $userId)->count(),
            'total_internships' => InternshipApplication::where('user_id', $userId)->count(),
            'total_jobs' => JobApplication::where('user_id', $userId)->count(),
            'certificates_earned' => \App\Models\Certificate::where('user_id', $userId)->count(),
        ];
    }

    public function getStudentPurchasedCourses(int $userId)
    {
        return CourseEnrollment::with(['course'])->where('user_id', $userId)->get();
    }

    public function getStudentInternships(int $userId)
    {
        return InternshipApplication::with(['internship'])->where('user_id', $userId)->get();
    }

    public function getStudentJobs(int $userId)
    {
        return JobApplication::with(['job.companyProfile'])->where('user_id', $userId)->get();
    }

    public function getStudentAttendance(int $userId)
    {
        return \App\Models\InternshipAttendance::with(['internship'])->where('user_id', $userId)->get();
    }
}
