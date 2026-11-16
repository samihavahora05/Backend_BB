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
        
        $applications = InternshipApplication::with('internship.company.companyProfile')
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();
            
        $recentApps = $applications->map(function ($app) {
            $companyName = $app->internship?->company?->companyProfile?->company_name 
                ?? $app->internship?->company_name 
                ?? $app->internship?->company?->name 
                ?? 'Company';
            return [
                'company' => $companyName,
                'role' => $app->internship?->title ?? 'Internship Role',
                'status' => $app->status,
                'time' => $app->created_at ? $app->created_at->diffForHumans() : '',
                'type' => $app->internship?->type ?? 'Internship'
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
        
        $applications = InternshipApplication::with('internship.company.companyProfile')
            ->where('user_id', $userId)
            ->latest()
            ->get();
            
        $apps = $applications->map(function ($app) {
            $companyName = $app->internship?->company?->companyProfile?->company_name 
                ?? $app->internship?->company_name 
                ?? $app->internship?->company?->name 
                ?? 'Company';
            $companyLogo = $app->internship?->company?->companyProfile?->logo 
                ?? $app->internship?->company_logo 
                ?? null;

            return [
                'id' => $app->id,
                'role' => $app->internship?->title ?? 'Internship Role',
                'company' => $companyName,
                'location' => $app->internship?->location ?? 'Remote',
                'status' => $app->status,
                'type' => $app->internship?->type ?? 'Internship',
                'logo' => $companyLogo ? asset('storage/' . $companyLogo) : "https://ui-avatars.com/api/?name=".urlencode($companyName)."&background=random"
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

    /**
     * Get Appointment Letter details for Intern review & E-Sign
     * GET /api/intern/appointment-letter
     */
    public function getAppointmentLetter(Request $request)
    {
        $user = $request->user();

        // Find active application or create a default onboarding appointment application
        $app = \App\Models\InternshipApplication::with(['appointmentLetter', 'internship'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        // If no formal application yet, link with qualified assessment attempt
        $attempt = \App\Models\AssessmentAttempt::where('user_id', $user->id)
            ->where('score', '>=', 30)
            ->where('status', 'completed')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'has_qualified' => (bool)$attempt,
                'application' => $app,
                'is_signed' => !empty($app?->signed_at) || !empty($app?->signature_path),
                'signed_at' => $app?->signed_at?->toISOString(),
                'status' => $app?->status ?? ($attempt ? 'ready_for_esign' : 'pending_assessment'),
                'candidate_name' => $user->name,
                'candidate_email' => $user->email,
                'candidate_phone' => $user->phone,
                'appointment_letter' => $app?->appointmentLetter,
            ]
        ]);
    }

    /**
     * E-Sign Appointment Letter (Save Drawn or Uploaded Base64 Signature)
     * POST /api/intern/appointment-letter/sign
     */
    public function signAppointmentLetter(Request $request)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $user = $request->user();
        $sigData = $request->input('signature');

        // Find existing application or create an approved internship onboarding record
        $app = \App\Models\InternshipApplication::where('user_id', $user->id)->latest()->first();

        if (!$app) {
            $internship = \App\Models\Internship::first();
            $app = \App\Models\InternshipApplication::create([
                'user_id' => $user->id,
                'internship_id' => $internship?->id ?? 1,
                'status' => 'pending_approval',
                'applicant_name' => $user->name,
                'applicant_email' => $user->email,
                'applicant_phone' => $user->phone,
            ]);
        }

        // Process Base64 Signature image and save to storage
        $filename = null;
        if (str_starts_with($sigData, 'data:image')) {
            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $type)) {
                $data = substr($sigData, strpos($sigData, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $type = 'png';
                }
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $filename = 'signatures/signature_' . $user->id . '_' . time() . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);
                    \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $decoded);
                }
            }
        }

        $app->update([
            'signature_path' => $filename,
            'candidate_signature_base64' => $sigData,
            'signed_at' => now(),
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'status' => 'signed_pending_approval',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment Letter E-Signed successfully. Submitted to Admin for final approval.',
            'data' => [
                'application_id' => $app->id,
                'signed_at' => $app->signed_at->toISOString(),
                'status' => $app->status,
                'signature_url' => $filename ? asset('storage/' . $filename) : null,
            ]
        ]);
    }

    /**
     * Download Appointment Letter PDF
     * GET /api/intern/appointment-letter/download
     */
    public function downloadAppointmentLetter(Request $request)
    {
        $user = $request->user();
        $app = \App\Models\InternshipApplication::with('appointmentLetter')->where('user_id', $user->id)->latest()->first();

        if (!$app) {
            return response()->json(['success' => false, 'message' => 'No application record found.'], 404);
        }

        $service = app(\App\Services\AppointmentLetterService::class);
        return $service->download($app);
    }
}
