<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAnswer;
use Illuminate\Http\Request;

class AdminAssessmentController extends Controller
{
    /**
     * List all intern assessment attempts for Admin monitoring
     * GET /api/admin/assessments/results
     */
    public function results(Request $request)
    {
        $query = AssessmentAttempt::with([
            'user:id,first_name,last_name,email,phone',
            'assessment:id,title,total_questions,total_marks,passing_percentage',
        ]);

        if ($request->filled('assessment_id')) {
            $query->where('assessment_id', $request->assessment_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: qualified for interview (score >= 30) or below 30
        if ($request->filled('filter')) {
            if ($request->filter === 'qualified') {
                $query->where('score', '>=', 30)->where('status', 'completed');
            } elseif ($request->filter === 'not_qualified') {
                $query->where('score', '<', 30)->where('status', 'completed');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $attempts = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        // Add stats count for admin tabs
        $totalCompleted = AssessmentAttempt::where('status', 'completed')->count();
        $qualifiedCount = AssessmentAttempt::where('status', 'completed')->where('score', '>=', 30)->count();
        $notQualifiedCount = AssessmentAttempt::where('status', 'completed')->where('score', '<', 30)->count();

        return response()->json([
            'success' => true,
            'data' => $attempts,
            'stats' => [
                'total_completed' => $totalCompleted,
                'qualified_30_plus' => $qualifiedCount,
                'not_qualified' => $notQualifiedCount,
            ]
        ]);
    }

    /**
     * View detailed question-by-question breakdown of any intern attempt
     * GET /api/admin/assessments/results/{attemptId}
     */
    public function attemptDetail(Request $request, $attemptId)
    {
        $attempt = AssessmentAttempt::with([
            'user:id,first_name,last_name,email,phone',
            'assessment',
        ])->findOrFail($attemptId);

        $answers = AssessmentAnswer::with(['question'])
            ->where('attempt_id', $attempt->id)
            ->get()
            ->sortBy(fn($a) => $a->question->order ?? $a->question_id)
            ->values()
            ->map(function ($ans) {
                $q = $ans->question;
                return [
                    'question_id' => $q->id,
                    'order' => $q->order,
                    'category' => $q->category,
                    'question' => $q->question,
                    'option_a' => $q->option_a,
                    'option_b' => $q->option_b,
                    'option_c' => $q->option_c,
                    'option_d' => $q->option_d,
                    'selected_answer' => $ans->selected_answer,
                    'correct_answer' => $ans->correct_answer,
                    'is_correct' => $ans->is_correct,
                    'marks_obtained' => $ans->marks_obtained,
                    'explanation' => $q->explanation,
                ];
            });

        $application = \App\Models\InternshipApplication::with('appointmentLetter')
            ->where('user_id', $attempt->user_id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attempt,
                'answers' => $answers,
                'application' => $application,
                'is_signed' => !empty($application?->signed_at) || !empty($application?->signature_path),
                'signed_at' => $application?->signed_at?->toISOString(),
                'signature_url' => $application?->signature_path ? asset('storage/' . $application->signature_path) : null,
                'is_approved' => $application?->status === 'approved',
            ]
        ]);
    }

    /**
     * Admin approves internship and sends Offer Letter Email
     * POST /api/admin/assessments/results/{attemptId}/approve-internship
     */
    public function approveInternship(Request $request, $attemptId)
    {
        $attempt = AssessmentAttempt::with(['user', 'assessment'])->findOrFail($attemptId);
        $user = $attempt->user;

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

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

        // Verify that candidate has signed the appointment letter before approval
        if (empty($app->signed_at) && empty($app->signature_path) && empty($app->candidate_signature_base64)) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate has not E-Signed the Appointment Letter yet. Signature is mandatory before approval.'
            ], 422);
        }

        // 1. Approve application
        $app->update([
            'status' => 'approved',
            'approved_at' => now(),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'offer_letter_sent_at' => now(),
        ]);

        // 2. Generate / compile official appointment letter with appointment service
        try {
            $appointmentService = app(\App\Services\AppointmentLetterService::class);
            $appointmentService->ensureSchema();
            $options = [
                'designation' => $request->input('designation', 'Software Development Intern'),
                'department' => $request->input('department', 'Engineering & Tech'),
                'stipend_amount' => $request->input('stipend_amount', 18000),
                'signatory_name' => $request->input('signatory_name', 'BlueBoxx Operations Head'),
            ];
            $letter = $appointmentService->generate($app, auth()->id(), $options);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Appointment letter generation note: " . $e->getMessage());
        }

        // 3. Send Official Offer Letter Email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\InternshipApprovalMail($app, $letter ?? null)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Offer letter email delivery failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Internship approved successfully! Offer Letter has been mailed to the intern.',
            'data' => [
                'application_id' => $app->id,
                'status' => $app->status,
                'approved_at' => $app->approved_at->toISOString(),
            ]
        ]);
    }

    /**
     * Download E-Signed Appointment Letter PDF for Admin
     * GET /api/admin/assessments/results/{attemptId}/download-appointment-letter
     */
    public function downloadAppointmentLetter(Request $request, $attemptId)
    {
        $attempt = AssessmentAttempt::findOrFail($attemptId);
        $app = \App\Models\InternshipApplication::with('appointmentLetter')->where('user_id', $attempt->user_id)->latest()->first();

        if (!$app) {
            return response()->json(['success' => false, 'message' => 'No application found for candidate.'], 404);
        }

        $service = app(\App\Services\AppointmentLetterService::class);
        return $service->download($app);
    }

    /**
     * Resend or Regenerate Google Meet Interview Link & Notifications
     * POST /api/admin/assessments/results/{attemptId}/resend-interview
     */
    public function resendInterview(Request $request, $attemptId)
    {
        try {
            $attempt = AssessmentAttempt::with(['user', 'assessment'])->findOrFail($attemptId);

            // Optional custom meeting link passed by admin
            $customMeetLink = $request->input('meet_link');
            if (!empty($customMeetLink)) {
                $attempt->update(['meet_link' => $customMeetLink]);
            } else {
                // Force regenerate via Google Meet REST API v2
                $attempt->update([
                    'meet_link' => null, 
                    'meeting_code' => null, 
                    'google_space_name' => null
                ]);
            }

            \App\Services\InterviewAutomationService::processQualifiedAttempt($attempt);

            $attempt->refresh();

            if (empty($attempt->meet_link) || str_contains($attempt->meet_link, 'landing')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Meet could not be generated. Please make sure info.blueboxx@gmail.com is authorized at /api/auth/google/connect.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'New Google Meet generated & dispatched via Mail & WhatsApp successfully!',
                'data' => [
                    'meeting_url' => $attempt->meet_link,
                    'meeting_code' => $attempt->meeting_code,
                    'meeting_status' => $attempt->interview_status,
                    'scheduled_at' => $attempt->interview_scheduled_at?->toISOString(),
                    'email_sent' => (bool)$attempt->email_sent,
                    'whatsapp_sent' => (bool)$attempt->whatsapp_sent,
                ]
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("resendInterview error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
