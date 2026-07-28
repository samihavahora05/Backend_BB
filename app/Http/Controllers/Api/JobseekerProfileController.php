<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobSeekerProfile;
use App\Models\StudentEducation;
use App\Models\StudentSkill;

class JobseekerProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        $profile = JobSeekerProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active', 'profile_completion' => 10]
        );

        $education = StudentEducation::where('user_id', $user->id)->get();
        // The JobSeekerProfile has a json 'skills' field, we can use that or StudentSkill. 
        // Let's stick to the JobSeekerProfile fields.

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null
                ],
                'profile' => $profile,
                'education' => $education
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = JobSeekerProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            $profile = JobSeekerProfile::create(['user_id' => $user->id]);
        }

        $validated = $request->validate([
            'phone' => 'nullable|string',
            'experience' => 'nullable|integer',
            'expected_salary' => 'nullable|numeric',
            'preferred_location' => 'nullable|string',
            'preferred_job_type' => 'nullable|string',
            'linkedin' => 'nullable|string',
            'github' => 'nullable|string',
            'portfolio' => 'nullable|string',
            'skills' => 'nullable|array',
            'headline' => 'nullable|string', // Note: If headline is not in DB, it might be in 'preferred_job_type' or we can add it
            'about_me' => 'nullable|string', // If not in DB, we can add it to JobSeekerProfile or just ignore
        ]);

        $profile->update($validated);
        
        // Also update User if name provided
        if ($request->has('first_name') || $request->has('last_name')) {
            $user->update($request->only(['first_name', 'last_name']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile
        ]);
    }

    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();
        $profile = JobSeekerProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            $profile = JobSeekerProfile::create(['user_id' => $user->id]);
        }

        if ($request->hasFile('resume')) {
            $path = $request->file('resume')->store('resumes', 'public');
            $profile->update(['resume_path' => $path]);
            
            // Increment profile completion if it's the first time
            if ($profile->profile_completion < 50) {
                $profile->increment('profile_completion', 20);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Resume uploaded successfully',
            'resume_path' => asset('storage/' . $profile->resume_path)
        ]);
    }
}
