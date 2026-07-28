<?php

namespace App\Services;

use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Models\ExpertActivityLog;
use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstructorService
{
    protected $repository;

    public function __construct(InstructorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function logActivity(int $expertId, int $adminId, string $action, ?string $notes = null)
    {
        ExpertActivityLog::create([
            'expert_id' => $expertId,
            'admin_id' => $adminId,
            'action' => $action,
            'notes' => $notes
        ]);
    }

    public function updateApprovalStatus(int $instructorId, string $status, int $adminId, ?string $notes = null)
    {
        return DB::transaction(function () use ($instructorId, $status, $adminId, $notes) {
            $profile = ExpertProfile::where('user_id', $instructorId)->firstOrFail();
            $oldStatus = $profile->approval_status;
            
            $profile->update(['approval_status' => $status]);
            
            // If approved, we can also auto-verify
            if ($status === 'approved') {
                $profile->update(['is_verified' => true]);
            }
            
            $this->logActivity(
                $instructorId, 
                $adminId, 
                "Changed status from {$oldStatus} to {$status}", 
                $notes
            );
            
            return $profile;
        });
    }

    public function resetPassword(int $instructorId, int $adminId)
    {
        $user = User::findOrFail($instructorId);
        $newPassword = Str::random(10);
        
        $user->update([
            'password' => Hash::make($newPassword)
        ]);
        
        $this->logActivity(
            $instructorId, 
            $adminId, 
            "Admin reset instructor password"
        );
        
        // In reality, dispatch an email job here
        
        return $newPassword;
    }

    public function assignCourse(int $instructorId, int $courseId, string $role, int $adminId)
    {
        $assignment = \App\Models\ExpertCourseAssignment::updateOrCreate(
            ['expert_id' => $instructorId, 'course_id' => $courseId],
            ['role' => $role]
        );
        
        $this->logActivity(
            $instructorId, 
            $adminId, 
            "Assigned course ID {$courseId} as {$role}"
        );
        
        return $assignment;
    }
}
