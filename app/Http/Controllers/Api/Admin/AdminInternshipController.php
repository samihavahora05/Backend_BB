<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipSubmission;
use App\Models\InternshipTask;
use App\Models\AppointmentLetter;
use App\Models\AuditLog;
use App\Mail\InternshipApprovalMail;
use App\Mail\InternshipRejectionMail;
use App\Services\AppointmentLetterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminInternshipController extends Controller
{
    protected AppointmentLetterService $appointmentService;

    public function __construct(AppointmentLetterService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    private function getSignatureDataUri(?string $path): ?string
    {
        if (empty($path) || !Storage::disk('local')->exists($path)) {
            return null;
        }
        $raw = Storage::disk('local')->get($path);
        $mime = (str_ends_with(strtolower($path), '.jpg') || str_ends_with(strtolower($path), '.jpeg')) ? 'image/jpeg' : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    /**
     * Get platform statistics for internships and applications.
     */
    public function stats()
    {
        $totalInternships = Internship::count();
        $activeInternships = Internship::where('status', 'open')->count();
        $totalApplications = InternshipApplication::count();
        $approvedApplications = InternshipApplication::where('status', 'approved')->count();
        $rejectedApplications = InternshipApplication::where('status', 'rejected')->count();
        $underReviewApplications = InternshipApplication::whereIn('status', ['under_review', 'submitted', 'pending'])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_internships'         => $totalInternships,
                'active_internships'        => $activeInternships,
                'total_applications'       => $totalApplications,
                'approved_applications'     => $approvedApplications,
                'rejected_applications'     => $rejectedApplications,
                'under_review_applications' => $underReviewApplications,
            ]
        ]);
    }

    /**
     * Get all internship applications across all internships.
     */
    public function allApplications(Request $request)
    {
        $query = InternshipApplication::with(['user', 'internship', 'appointmentLetter', 'reviewer']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('degree', 'like', "%{$search}%")
                  ->orWhereHas('internship', function($iq) use ($search) {
                      $iq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $query->where('status', $status);
        }

        $apps = $query->latest()->paginate($request->input('per_page', 15));

        $apps->through(function($app) {
            $app->applicant_name         = $app->applicant_name;
            $app->applicant_email        = $app->applicant_email;
            $app->applicant_phone        = $app->applicant_phone;
            $app->resume_download        = $app->resume_url ? asset('storage/' . $app->resume_url) : null;
            $app->signature_url          = $app->signature_url;
            $app->signature_data         = $this->getSignatureDataUri($app->signature_path);
            $app->appointment_letter_url = $app->appointment_letter_url;
            return $app;
        });

        return response()->json(['success' => true, 'data' => $apps]);
    }

    /**
     * Get applications for a specific internship.
     */
    public function applicationsByInternship(Request $request, $id)
    {
        $query = InternshipApplication::with(['user', 'appointmentLetter', 'reviewer'])
            ->where('internship_id', $id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->status));
        }

        $apps = $query->latest()->paginate($request->input('per_page', 15));

        $apps->through(function($app) {
            $app->applicant_name         = $app->applicant_name;
            $app->applicant_email        = $app->applicant_email;
            $app->applicant_phone        = $app->applicant_phone;
            $app->resume_download        = $app->resume_url ? asset('storage/' . $app->resume_url) : null;
            $app->signature_url          = $app->signature_url;
            $app->signature_data         = $this->getSignatureDataUri($app->signature_path);
            $app->appointment_letter_url = $app->appointment_letter_url;
            return $app;
        });

        return response()->json(['success' => true, 'data' => $apps]);
    }

    /**
     * Show detailed application.
     */
    public function showApplication($id)
    {
        $app = InternshipApplication::with(['user', 'internship', 'appointmentLetter', 'reviewer'])->findOrFail($id);

        $data = array_merge($app->toArray(), [
            'applicant_name'         => $app->applicant_name,
            'applicant_email'        => $app->applicant_email,
            'applicant_phone'        => $app->applicant_phone,
            'resume_download'        => $app->resume_url ? asset('storage/' . $app->resume_url) : null,
            'signature_url'          => $app->signature_url,
            'signature_data'         => $this->getSignatureDataUri($app->signature_path),
            'appointment_letter_url' => $app->appointment_letter_url,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    /**
     * Save Appointment Details Draft.
     * POST /api/admin/internships/applications/{id}/appointment-details
     */
    public function saveAppointmentDetails(Request $request, $id)
    {
        $app = InternshipApplication::findOrFail($id);

        $options = $this->extractAppointmentOptions($request);

        $record = AppointmentLetter::updateOrCreate(
            ['application_id' => $app->id],
            [
                'user_id'          => $app->user_id,
                'reference_number' => $options['reference_number'] ?? ('BB-AL-' . date('Y') . '-' . str_pad((string)$app->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4))),
                'file_path'        => $app->appointment_letter_path ?? '',
                'document_version' => 'v2.1',
                'generated_by'     => auth()->id(),
                'generated_at'     => now(),
                'metadata'         => $options,
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $record,
            'message' => 'Appointment details saved successfully.'
        ]);
    }

    /**
     * Approve an application, generate appointment letter atomically with dual signatures, and notify applicant.
     */
    public function approveApplication(Request $request, $id)
    {
        $app = InternshipApplication::with(['user', 'internship'])->findOrFail($id);

        return DB::transaction(function () use ($app, $request) {
            $app->status = 'approved';
            $app->approved_at = now();
            $app->reviewed_by = auth()->id();
            $app->reviewed_at = now();
            $app->save();

            $options = $this->extractAppointmentOptions($request);

            // Generate appointment letter with dual signatures & DejaVu Sans Unicode support
            $letter = $this->appointmentService->generate($app, auth()->id(), $options);

            // Audit log
            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'internship_application_approved',
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent() ?? 'System',
                'payload'    => [
                    'application_id'   => $app->id,
                    'reference_number' => $letter->reference_number,
                    'signatory_name'   => $options['signatory_name'] ?? 'Authorized Signatory',
                    'stipend'          => $options['stipend_amount'] ?? 18000,
                ]
            ]);

            // Email Notification
            try {
                if ($app->user && $app->user->email) {
                    Mail::to($app->user->email)->send(new InternshipApprovalMail($app, $letter));
                } elseif ($app->email) {
                    Mail::to($app->email)->send(new InternshipApprovalMail($app, $letter));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Approval email delivery failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data'    => $app->fresh(['appointmentLetter']),
                'message' => 'Application approved and official Appointment Letter generated successfully.'
            ]);
        });
    }

    /**
     * Extract structured appointment options from Request.
     */
    private function extractAppointmentOptions(Request $request): array
    {
        $adminSigPath = null;
        $adminSigData = null;

        if ($request->hasFile('admin_signature')) {
            $file = $request->file('admin_signature');
            $filename = 'admin_sig_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $adminSigPath = 'signatures/' . $filename;
            Storage::disk('local')->put($adminSigPath, file_get_contents($file));
        } elseif ($request->filled('admin_signature')) {
            $rawSig = $request->input('admin_signature');
            if (str_starts_with($rawSig, 'data:image')) {
                $adminSigData = $rawSig;
                $filename = 'admin_sig_' . time() . '_' . Str::random(10) . '.png';
                $adminSigPath = 'signatures/' . $filename;
                $base64Image = substr($rawSig, strpos($rawSig, ',') + 1);
                Storage::disk('local')->put($adminSigPath, base64_decode($base64Image));
            }
        }

        $workingDays = $request->input('working_days', 'Monday to Friday');
        if (is_string($workingDays) && (str_starts_with($workingDays, '[') || str_contains($workingDays, ','))) {
            $decoded = json_decode($workingDays, true);
            if (is_array($decoded)) {
                $workingDays = $decoded;
            }
        }

        return [
            'designation'           => $request->input('designation', 'Backend Developer Intern'),
            'department'            => $request->input('department', 'Engineering & Development'),
            'start_date'            => $request->input('start_date', now()->format('Y-m-d')),
            'end_date'              => $request->input('end_date'),
            'duration'              => $request->input('duration', '6 Months'),
            'working_days'          => $workingDays,
            'working_hours'         => $request->input('working_hours', '09:30 AM - 06:30 PM'),
            'break_time'            => $request->input('break_time', '01:00 PM - 02:00 PM'),
            'reporting_time'        => $request->input('reporting_time', '09:30 AM'),
            'stipend_amount'        => $request->input('stipend_amount', 18000),
            'stipend_currency'      => $request->input('stipend_currency', '₹'),
            'payment_frequency'     => $request->input('payment_frequency', 'month'),
            'reporting_to'          => $request->input('reporting_to', 'Technical Project Manager'),
            'reporting_person_name' => $request->input('reporting_person_name', 'Authorized Signatory'),
            'work_location'         => $request->input('work_location', 'Vadodara, Gujarat'),
            'work_mode'             => $request->input('work_mode', 'Onsite'),
            'issue_date'            => $request->input('issue_date', now()->format('Y-m-d')),
            'reference_number'      => $request->input('reference_number'),
            'signatory_name'        => $request->input('signatory_name', 'Authorized Signatory'),
            'signatory_designation' => $request->input('signatory_designation', 'Director / HR Head'),
            'admin_signature_path'  => $adminSigPath,
            'admin_signature_data'  => $adminSigData,
        ];
    }

    /**
     * Reject an application with reason.
     */
    public function rejectApplication(Request $request, $id)
    {
        $app = InternshipApplication::with(['user', 'internship'])->findOrFail($id);

        $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $reason = $request->input('rejection_reason');

        $app->status = 'rejected';
        $app->rejection_reason = $reason;
        $app->reviewed_by = auth()->id();
        $app->reviewed_at = now();
        $app->save();

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'internship_application_rejected',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'System',
            'payload'    => [
                'application_id'   => $app->id,
                'rejection_reason' => $reason,
            ]
        ]);

        try {
            if ($app->user && $app->user->email) {
                Mail::to($app->user->email)->send(new InternshipRejectionMail($app, $reason));
            } elseif ($app->email) {
                Mail::to($app->email)->send(new InternshipRejectionMail($app, $reason));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Rejection email delivery failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data'    => $app,
            'message' => 'Application marked as rejected.'
        ]);
    }

    /**
     * Update status and internal notes.
     */
    public function updateApplicationStatus(Request $request, $id)
    {
        $app = InternshipApplication::findOrFail($id);

        $request->validate([
            'status'         => 'required|in:submitted,applied,pending,under_review,shortlisted,interview,approved,selected,completed,rejected,cancelled',
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $app->status = $request->status;
        if ($request->has('internal_notes')) {
            $app->internal_notes = $request->internal_notes;
        }
        $app->reviewed_by = auth()->id();
        $app->reviewed_at = now();
        $app->save();

        return response()->json([
            'success' => true,
            'data'    => $app,
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * Admin download appointment letter.
     */
    public function downloadAppointmentLetter(Request $request, $id)
    {
        $app = InternshipApplication::with('appointmentLetter')->findOrFail($id);

        if (empty($app->appointment_letter_path) || !Storage::disk('local')->exists($app->appointment_letter_path) || $request->has('regenerate')) {
            $letter = $this->appointmentService->generate($app, auth()->id());
            $app->refresh();
        }

        $path = Storage::disk('local')->path($app->appointment_letter_path);
        $ref = $app->appointmentLetter?->reference_number ?? ('AL_' . $app->id);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Appointment_Letter_' . $ref . '.pdf"',
        ]);
    }

    /**
     * Get all internship task submissions across all internships.
     */
    public function allSubmissions(Request $request)
    {
        $query = InternshipSubmission::with(['task.internship', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('submission_text', 'like', "%{$search}%")
                  ->orWhere('github_link', 'like', "%{$search}%")
                  ->orWhere('video_link', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('task', function($tq) use ($search) {
                      $tq->where('title', 'like', "%{$search}%")
                         ->orWhereHas('internship', function($iq) use ($search) {
                             $iq->where('title', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $query->where('status', $status);
        }

        if ($request->filled('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        $submissions = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $submissions
        ]);
    }
}
