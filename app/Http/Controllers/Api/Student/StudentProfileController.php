<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\StudentPreference;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentProfileController extends Controller
{
    /**
     * Get the student profile data.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $profile = StudentProfile::firstOrCreate(['user_id' => $user->id]);
        $pref = StudentPreference::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'location' => $profile->city ?? '',
                'bio' => $profile->bio ?? '',
                'avatar' => $profile->profile_photo ? asset('storage/' . $profile->profile_photo) : null,
                'notifications' => $pref->notification_preferences ?? [
                    'assignments' => true,
                    'liveClasses' => true,
                    'applications' => true,
                    'certificates' => false,
                    'newsletter' => false,
                ],
                'privacy' => $pref->privacy_settings ?? [
                    'showProfile' => true,
                    'showLeaderboard' => false,
                    'dataAnalytics' => true,
                ],
            ]
        ]);
    }

    /**
     * Update the student profile data.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'notifications' => 'nullable|array',
            'privacy' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
            $user->save();
        }

        $profile = StudentProfile::firstOrCreate(['user_id' => $user->id]);
        
        if ($request->has('location')) $profile->city = $request->location;
        if ($request->has('bio')) $profile->bio = $request->bio;
        $profile->save();

        $pref = StudentPreference::firstOrCreate(['user_id' => $user->id]);
        if ($request->has('notifications')) $pref->notification_preferences = $request->notifications;
        if ($request->has('privacy')) $pref->privacy_settings = $request->privacy;
        $pref->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        // Just disable or delete
        $user->is_active = false;
        $user->status = 'inactive';
        $user->save();

        return response()->json(['success' => true, 'message' => 'Account deleted/deactivated successfully']);
    }
}
