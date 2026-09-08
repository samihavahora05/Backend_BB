<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\InternshipApplication;
use App\Models\ScholarshipApplication;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class StudentApplicationController extends Controller
{
    /**
     * Get all applications for the unified My Applications dashboard.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $applications = collect();

        // 1. Job Applications
        $jobs = JobApplication::with('job:id,title,company_id')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id'         => 'job_'.$app->id,
                    'type'       => 'Job',
                    'title'      => $app->job->title ?? 'Job Application',
                    'status'     => $app->status,
                    'applied_on' => $app->created_at,
                    'link'       => '/student/jobs'
                ];
            });
        $applications = $applications->concat($jobs);

        // 2. Internship Applications (enriched with T&C, signature, and appointment letter links)
        $internships = InternshipApplication::with(['internship:id,title,company_name,location,duration,stipend', 'appointmentLetter'])
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id'                     => 'internship_'.$app->id,
                    'raw_id'                 => $app->id,
                    'type'                   => 'Internship',
                    'title'                  => $app->internship->title ?? $app->application_type ?? 'Internship Application',
                    'status'                 => $app->status,
                    'terms_accepted'         => (bool)$app->terms_accepted,
                    'terms_version'          => $app->terms_version,
                    'signature_url'          => $app->signature_url,
                    'rejection_reason'       => $app->rejection_reason,
                    'appointment_letter_url' => $app->appointment_letter_url,
                    'has_appointment_letter' => !empty($app->appointment_letter_path),
                    'applied_on'             => $app->applied_at ?? $app->created_at,
                    'link'                   => '/student/internships'
                ];
            });
        $applications = $applications->concat($internships);

        // 3. Scholarship Applications
        $scholarships = ScholarshipApplication::with('program:id,name')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($app) {
                return [
                    'id'         => 'scholarship_'.$app->id,
                    'type'       => 'Scholarship',
                    'title'      => $app->program->name ?? 'Scholarship Program',
                    'status'     => $app->status,
                    'applied_on' => $app->created_at,
                    'link'       => '/student/scholarships'
                ];
            });
        $applications = $applications->concat($scholarships);

        // Sort by applied_on desc
        $sortedApplications = $applications->sortByDesc('applied_on')->values()->map(function ($app) {
            $app['applied_on_formatted'] = $app['applied_on'] ? $app['applied_on']->format('M d, Y') : 'Just now';
            return $app;
        });

        return response()->json([
            'success' => true,
            'data'    => $sortedApplications
        ]);
    }

    /**
     * Secure endpoint for applicant to download appointment letter (Strict IDOR protection)
     * GET /api/student/applications/{id}/appointment-letter
     */
    public function downloadAppointmentLetter(Request $request, $id)
    {
        $user = $request->user();
        $app = InternshipApplication::with('appointmentLetter')->findOrFail($id);

        // IDOR Check
        if ($app->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this document.'], 403);
        }

        if ($app->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Appointment Letter is not yet approved by administration.'], 403);
        }

        if (empty($app->appointment_letter_path) || !Storage::disk('local')->exists($app->appointment_letter_path)) {
            $service = app(\App\Services\AppointmentLetterService::class);
            $service->generate($app);
            $app->refresh();
        }

        // Record Audit Log
        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'appointment_letter_downloaded_by_student',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'System',
            'payload'    => [
                'application_id'   => $app->id,
                'reference_number' => $app->appointmentLetter?->reference_number,
            ],
        ]);

        if ($app->appointmentLetter) {
            $app->appointmentLetter->update(['downloaded_at' => now()]);
        }

        $path = Storage::disk('local')->path($app->appointment_letter_path);
        $candidateName = Str::slug($app->applicant_name ?: ($app->first_name . ' ' . $app->last_name), '_');
        $filename = 'BlueBoxx_Appointment_Letter_' . ($candidateName ?: 'Candidate_' . $app->id) . '.pdf';

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}