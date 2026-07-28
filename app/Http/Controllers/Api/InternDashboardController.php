<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InternshipApplication;
use App\Models\InternshipTask;

class InternDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        $applications = InternshipApplication::with('internship.companyProfile')
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();
            
        $recentApps = $applications->map(function ($app) {
            return [
                'company' => $app->internship->companyProfile->company_name ?? 'Company',
                'role' => $app->internship->title ?? 'Internship Role',
                'status' => $app->status,
                'time' => $app->created_at->diffForHumans(),
                'type' => $app->internship->type ?? 'Internship'
            ];
        });

        $tasksCompleted = InternshipTask::whereHas('internship', function($q) use ($userId) {
            $q->whereHas('applications', function($q2) use ($userId) {
                $q2->where('user_id', $userId)->where('status', 'Hired');
            });
        })->where('status', 'Completed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'applications' => InternshipApplication::where('user_id', $userId)->count(),
                    'tasks_completed' => $tasksCompleted,
                    'hours_logged' => 0, // TBD: Implement actual hours logged based on tasks or timesheet
                ],
                'recent_applications' => $recentApps
            ]
        ]);
    }

    public function applications(Request $request)
    {
        $userId = $request->user()->id;
        
        $applications = InternshipApplication::with('internship.companyProfile')
            ->where('user_id', $userId)
            ->latest()
            ->get();
            
        $apps = $applications->map(function ($app) {
            return [
                'id' => $app->id,
                'role' => $app->internship->title ?? 'Internship Role',
                'company' => $app->internship->companyProfile->company_name ?? 'Company',
                'location' => $app->internship->location ?? 'Remote',
                'status' => $app->status,
                'type' => $app->internship->type ?? 'Internship',
                'logo' => $app->internship->companyProfile->logo ? asset('storage/' . $app->internship->companyProfile->logo) : "https://ui-avatars.com/api/?name=".urlencode($app->internship->companyProfile->company_name ?? 'C')."&background=random"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $apps
        ]);
    }

    public function mentorSessions(Request $request)
    {
        $userId = $request->user()->id;
        
        // Return empty array for now since MentorBooking isn't fully linked
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    public function settings(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ]
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ]
        ]);
    }

    public function resume(Request $request)
    {
        $user = $request->user();
        $resume = $user->documents()->where('document_type', 'resume')->first();
        
        return response()->json([
            'success' => true,
            'data' => $resume ? [
                'name' => basename($resume->file_path),
                'url' => asset('storage/' . $resume->file_path),
                'size' => 'Unknown', // Could calculate from file
                'uploaded_at' => $resume->created_at->format('M d, Y'),
            ] : null
        ]);
    }

    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();
        $file = $request->file('resume');
        
        $path = $file->store('interns/resumes/' . $user->id, 'public');

        // Delete old resume
        $oldResume = $user->documents()->where('document_type', 'resume')->first();
        if ($oldResume) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldResume->file_path);
            $oldResume->update(['file_path' => $path]);
            $resume = $oldResume;
        } else {
            $resume = $user->documents()->create([
                'document_type' => 'resume',
                'file_path' => $path,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Resume uploaded successfully',
            'data' => [
                'name' => basename($resume->file_path),
                'url' => asset('storage/' . $resume->file_path),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                'uploaded_at' => 'Just now',
            ]
        ]);
    }
}
