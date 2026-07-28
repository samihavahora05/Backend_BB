<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('roles');
        $profile = $this->getProfileForUser($user);

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'profile' => $profile,
            'completion_percentage' => $this->calculateCompletionPercentage($profile)
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $this->getProfileForUser($user);

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        // Validate basic fields that all profiles might share, plus flexible ones
        // In a strict environment, you would use separate FormRequests per role.
        $validatedData = $request->except(['id', 'user_id', 'profile_completion', 'is_verified', 'status', 'created_at', 'updated_at']);

        // Only allow fields that actually exist on this profile's table
        $table = $profile->getTable();
        $columns = Schema::getColumnListing($table);
        
        $dataToUpdate = [];
        foreach ($validatedData as $key => $value) {
            if (in_array($key, $columns)) {
                $dataToUpdate[$key] = $value;
            }
        }

        $profile->forceFill($dataToUpdate)->save();

        // Recalculate completion and update
        $completion = $this->calculateCompletionPercentage($profile);
        $profile->forceFill(['profile_completion' => $completion])->save();

        // Update User table basics if provided
        if ($request->has('name') || $request->has('phone')) {
            $user->update($request->only(['name', 'phone']));
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
            'completion_percentage' => $completion
        ]);
    }

    private function getProfileForUser($user)
    {
        if ($user->hasRole('student')) return $user->studentProfile()->first();
        if ($user->hasRole('expert')) return $user->expertProfile()->first();
        if ($user->hasRole('company')) return $user->companyProfile()->first();
        if ($user->hasRole('college')) return $user->collegeProfile()->first();
        if ($user->hasRole('intern')) return $user->internProfile()->first();
        if ($user->hasRole('job-seeker')) return $user->jobSeekerProfile()->first();
        return null;
    }

    private function calculateCompletionPercentage($profile)
    {
        if (!$profile) return 0;

        $table = $profile->getTable();
        $columns = Schema::getColumnListing($table);
        
        // Exclude system columns from calculation
        $excluded = ['id', 'user_id', 'profile_completion', 'is_verified', 'status', 'created_at', 'updated_at'];
        $calculableColumns = array_diff($columns, $excluded);
        
        if (count($calculableColumns) === 0) return 100;

        $filled = 0;
        foreach ($calculableColumns as $column) {
            if (!empty($profile->{$column})) {
                $filled++;
            }
        }

        return (int) round(($filled / count($calculableColumns)) * 100);
    }

    public function updateRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = $request->user();
        
        // Only allow updating role if they are fresh or doing it during onboarding
        // For simplicity, we just sync the role.
        $user->syncRoles([$request->role]);

        // Auto-provision profile based on new role if it doesn't exist
        match ($request->role) {
            'student' => $user->studentProfile()->firstOrCreate([]),
            'expert' => $user->expertProfile()->firstOrCreate([]),
            'company' => $user->companyProfile()->firstOrCreate([]),
            'college' => $user->collegeProfile()->firstOrCreate([]),
            'intern' => $user->internProfile()->firstOrCreate([]),
            'job-seeker' => $user->jobSeekerProfile()->firstOrCreate([]),
            'jobseeker' => $user->jobSeekerProfile()->firstOrCreate([]),
            default => null,
        };

        return response()->json([
            'message' => 'Role assigned successfully.',
            'role' => $request->role
        ]);
    }
}
