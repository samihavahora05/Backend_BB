<?php

namespace App\Services;

use App\Models\User;

class OpportunityPermissionService
{
    /**
     * Helper to extract role safely from User instance
     */
    public static function getUserRole(?User $user): string
    {
        if (!$user) {
            return '';
        }

        // 1. Check raw attribute if set directly
        if (!empty($user->attributes['role'])) {
            return strtolower(trim(str_replace('_', '-', $user->attributes['role'])));
        }

        // 2. Check getRoleAttribute accessor
        try {
            $roleAttr = $user->role;
            if (!empty($roleAttr)) {
                return strtolower(trim(str_replace('_', '-', $roleAttr)));
            }
        } catch (\Throwable $e) {}

        // 3. Check Spatie roles relationship
        try {
            if ($user->relationLoaded('roles') && $user->roles->isNotEmpty()) {
                return strtolower(trim(str_replace('_', '-', $user->roles->first()->name ?? '')));
            }
            if (method_exists($user, 'roles') && $user->roles()->exists()) {
                return strtolower(trim(str_replace('_', '-', $user->roles()->first()->name ?? '')));
            }
        } catch (\Throwable $e) {}

        return 'student';
    }

    /**
     * Determine if a user can apply to a Job.
     * Allowed: job-seeker, jobseeker, super_admin, admin
     */
    public static function canApplyToJob(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = self::getUserRole($user);
        if (in_array($role, ['job-seeker', 'jobseeker', 'super-admin', 'admin'])) {
            return true;
        }

        try {
            return $user->hasAnyRole(['job-seeker', 'jobseeker', 'super_admin', 'admin', 'super-admin']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Determine if a user can apply to an Internship.
     * Allowed: intern, super_admin, admin
     */
    public static function canApplyToInternship(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = self::getUserRole($user);
        if (in_array($role, ['intern', 'super-admin', 'admin'])) {
            return true;
        }

        try {
            return $user->hasAnyRole(['intern', 'super_admin', 'admin', 'super-admin']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Determine if a user can apply to / enroll in a Course.
     * Allowed: ALL active authenticated roles (student, intern, jobseeker, expert, college, company, admin)
     */
    public static function canApplyToCourse(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isActive();
    }

    /**
     * Determine if a role change is allowed between roles.
     * Only applicant roles (student, intern, jobseeker) can be transitioned to.
     * Requesting admin, college, company is prohibited.
     */
    public static function canRequestRoleChange(?User $user, string $targetRole): bool
    {
        if (!$user) {
            return false;
        }

        $normalizedTarget = strtolower(trim(str_replace('_', '-', $targetRole)));
        $allowedTargets = ['student', 'intern', 'job-seeker', 'jobseeker'];

        if (!in_array($normalizedTarget, $allowedTargets)) {
            return false;
        }

        $currentRole = self::getUserRole($user);
        
        // Cannot request same role
        if ($currentRole === $normalizedTarget || ($currentRole === 'jobseeker' && $normalizedTarget === 'job-seeker') || ($currentRole === 'job-seeker' && $normalizedTarget === 'jobseeker')) {
            return false;
        }

        // Only applicant and expert roles can request role changes
        $allowedRequesters = ['student', 'intern', 'job-seeker', 'jobseeker', 'expert'];
        return in_array($currentRole, $allowedRequesters);
    }

    /**
     * Get descriptive permission status and message for frontend / API.
     */
    public static function getPermissionStatus(?User $user, string $type): array
    {
        if (!$user) {
            return [
                'can_apply' => false,
                'is_authenticated' => false,
                'message' => 'Please log in to apply for this opportunity.',
                'code' => 'UNAUTHENTICATED',
                'target_role' => null,
                'can_request_role_change' => false,
            ];
        }

        $currentRole = self::getUserRole($user);
        $currentRoleDisplay = match ($currentRole) {
            'student' => 'Student',
            'intern' => 'Intern',
            'job-seeker', 'jobseeker' => 'Jobseeker',
            'expert' => 'Expert',
            'college' => 'College',
            'company' => 'Company',
            'admin', 'super-admin' => 'Admin',
            default => ucfirst($currentRole),
        };

        if ($type === 'job') {
            $allowed = self::canApplyToJob($user);
            if ($allowed) {
                return [
                    'can_apply' => true,
                    'is_authenticated' => true,
                    'current_role' => $currentRole,
                    'message' => 'You are eligible to apply for Jobs.',
                    'code' => 'ELIGIBLE',
                    'target_role' => null,
                    'can_request_role_change' => false,
                ];
            }

            $message = match ($currentRole) {
                'student' => 'You are currently registered as a Student. Students cannot apply for Jobs.',
                'intern' => 'You are currently registered as an Intern. Interns can apply for Internships, not Jobs.',
                'expert' => 'You are currently registered as an Expert. Experts cannot apply for Jobs.',
                'college' => 'College accounts manage student placements and cannot submit candidate applications.',
                'company' => 'Company accounts post hiring opportunities and cannot apply for Jobs.',
                default => "You are registered as a {$currentRoleDisplay} and cannot apply for Jobs.",
            };

            return [
                'can_apply' => false,
                'is_authenticated' => true,
                'current_role' => $currentRole,
                'message' => $message,
                'code' => 'ROLE_APPLICATION_RESTRICTED',
                'target_role' => 'jobseeker',
                'can_request_role_change' => self::canRequestRoleChange($user, 'jobseeker'),
            ];
        }

        if ($type === 'internship') {
            $allowed = self::canApplyToInternship($user);
            if ($allowed) {
                return [
                    'can_apply' => true,
                    'is_authenticated' => true,
                    'current_role' => $currentRole,
                    'message' => 'You are eligible to apply for Internships.',
                    'code' => 'ELIGIBLE',
                    'target_role' => null,
                    'can_request_role_change' => false,
                ];
            }

            $message = match ($currentRole) {
                'student' => 'You are currently registered as a Student. Students cannot apply for Internships.',
                'job-seeker', 'jobseeker' => 'You are currently registered as a Jobseeker. Jobseekers can apply for Jobs, not Internships.',
                'expert' => 'You are currently registered as an Expert. Experts cannot apply for Internships.',
                'college' => 'College accounts manage student drives and cannot submit candidate applications.',
                'company' => 'Company accounts post internship opportunities and cannot apply for Internships.',
                default => "You are registered as a {$currentRoleDisplay} and cannot apply for Internships.",
            };

            return [
                'can_apply' => false,
                'is_authenticated' => true,
                'current_role' => $currentRole,
                'message' => $message,
                'code' => 'ROLE_APPLICATION_RESTRICTED',
                'target_role' => 'intern',
                'can_request_role_change' => self::canRequestRoleChange($user, 'intern'),
            ];
        }

        // Courses - Open to everyone
        return [
            'can_apply' => self::canApplyToCourse($user),
            'is_authenticated' => true,
            'current_role' => $currentRole,
            'message' => 'You can apply and enroll in Courses.',
            'code' => 'ELIGIBLE',
            'target_role' => null,
            'can_request_role_change' => false,
        ];
    }
}