<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\AuditLog;
use App\Models\User;
use App\Mail\AdminNewInternshipApplicationMail;
use App\Services\AppointmentLetterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicInternshipController extends Controller
{
    /**
     * Public internship listing with search and filters
     * GET /api/public/internships
     */
    public function index(Request $request)
    {
        $query = Internship::query()->with('company.companyProfile')->whereIn('status', ['open', 'Open', 'active', 'published']);

        if ($s = $request->query('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%")
                  ->orWhereHas('company.companyProfile', function($query) use ($s) {
                      $query->where('company_name', 'like', "%{$s}%");
                  });
            });
        }

        if ($type = $request->query('type')) {
            $query->where('mode', $type);
        }
        if ($domain = $request->query('domain')) {
            $query->where(function($q) use ($domain) {
                $q->where('department', 'like', "%{$domain}%")
                  ->orWhere('title', 'like', "%{$domain}%");
            });
        }
        if ($duration = $request->query('duration')) {
            if (preg_match('/(\d+)\s*Year/i', $duration, $m)) {
                $months = (int) $m[1] * 12;
            } elseif (preg_match('/(\d+)/', $duration, $m)) {
                $months = (int) $m[1];
            } else {
                $months = null;
            }

            $query->where(function ($q) use ($duration, $months) {
                if ($months !== null) {
                    $q->where('duration_months', $months);
                }
                $q->orWhere('duration', 'like', "%{$duration}%");
            });
        }
        if ($level = $request->query('experience_level')) {
            $query->where('eligibility', 'like', "%{$level}%");
        }
        if ($request->boolean('paid')) {
            $query->whereNotNull('stipend')->where('stipend', '>', 0);
        }

        $perPage = min((int)$request->query('per_page', 12), 50);
        $internships = $query->latest()->paginate($perPage);

        $appliedInternshipIds = [];
        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        if ($user) {
            $appliedInternshipIds = InternshipApplication::where('user_id', $user->id)
                ->pluck('internship_id')
                ->toArray();
        }

        $data = $internships->through(fn($i) => [
            'id'           => $i->id,
            'title'        => $i->title,
            'company_name' => $i->company_name ?? ($i->company?->companyProfile?->company_name ?? $i->company?->name ?? 'Blueboxx Partner'),
            'company_logo' => $i->company_logo ? \App\Support\StorageHelper::url($i->company_logo) : null,
            'location'     => $i->location ?? 'Remote',
            'type'         => $i->mode ?? 'Remote',
            'duration'     => $i->duration ?? ($i->duration_months ? $i->duration_months . ' Months' : '3 Months'),
            'department'   => $i->department ?? 'General',
            'is_paid'      => $i->stipend > 0,
            'stipend'      => $i->stipend ?? 0,
            'stipend_text' => $i->stipend > 0 ? ('₹' . number_format($i->stipend) . ' / month') : 'Performance Based',
            'start_date'   => $i->start_date ? \Carbon\Carbon::parse($i->start_date)->format('M d, Y') : null,
            'last_date'    => $i->application_deadline ? \Carbon\Carbon::parse($i->application_deadline)->format('M d, Y') : null,
            'posted_at'    => $i->created_at ? $i->created_at->diffForHumans() : 'Recently',
            'is_featured'  => (bool)($i->featured ?? false),
            'has_applied'  => in_array($i->id, $appliedInternshipIds),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Public internship detail
     * GET /api/public/internships/{id}
     */
    public function show(Request $request, $id)
    {
        $internship = Internship::with('company.companyProfile')->whereIn('status', ['open', 'Open', 'active', 'published'])->findOrFail($id);

        $hasApplied = false;
        $isBookmarked = false;
        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        if ($user) {
            $hasApplied = InternshipApplication::where('internship_id', $internship->id)
                ->where('user_id', $user->id)
                ->exists();
            $isBookmarked = \App\Models\SavedInternship::where('internship_id', $internship->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data'    => array_merge($internship->toArray(), [
                'company_logo' => $internship->company_logo ? \App\Support\StorageHelper::url($internship->company_logo) : null,
                'has_applied'  => $hasApplied,
                'is_bookmarked'=> $isBookmarked,
                'posted_at'    => $internship->created_at ? $internship->created_at->diffForHumans() : 'Recently',
            ])
        ]);
    }

    /**
     * Helper to process digital signature upload / base64 string
     */
    protected function saveDigitalSignature(Request $request): ?string
    {
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $filename = 'signature_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            return $file->storeAs('signatures', $filename, 'local');
        }

        $sigData = $request->input('signature');
        if (!empty($sigData) && is_string($sigData) && str_starts_with($sigData, 'data:image')) {
            // Base64 data URL
            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $type)) {
                $data = substr($sigData, strpos($sigData, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $type = 'png';
                }
                $data = base64_decode($data);
                if ($data === false) {
                    return null;
                }
                $filename = 'signatures/signature_' . time() . '_' . Str::random(12) . '.' . $type;
                Storage::disk('local')->put($filename, $data);
                return $filename;
            }
        }

        return null;
    }

    /**
     * Apply for an internship
     * POST /api/public/internships/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $internship = Internship::whereIn('status', ['open', 'Open', 'active', 'published'])->find($id);

        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        if ($user && $internship) {
            $alreadyApplied = InternshipApplication::where('internship_id', $internship->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($alreadyApplied) {
                return response()->json(['success' => false, 'message' => 'You have already applied for this internship.'], 422);
            }
        }

        $data = $request->validate([
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'degree'           => 'nullable|string|max:255',
            'graduation_year'  => 'nullable|string|max:50',
            'message'          => 'nullable|string|max:5000',
            'cover_letter'     => 'nullable|string|max:5000',
            'resume'           => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'portfolio_url'    => 'nullable|string|max:1000',
            'github_url'       => 'nullable|string|max:1000',
            'linkedin_url'     => 'nullable|string|max:1000',
            'application_type' => 'nullable|string|max:255',
            'source_page'      => 'nullable|string|max:255',
            'experience_years' => 'nullable|string|max:100',
            'current_company'  => 'nullable|string|max:255',
            'available_from'   => 'nullable|string|max:100',
            'expected_stipend' => 'nullable|string|max:100',
            // Mandatory T&C Agreement and Digital Signature
            'terms_accepted'   => 'required|in:1,true,yes,on',
            'terms_version'    => 'nullable|string|max:50',
            'signature'        => 'required',
        ], [
            'terms_accepted.required' => 'You must agree to the Terms & Conditions before submitting.',
            'terms_accepted.in'       => 'You must agree to the Terms & Conditions before submitting.',
            'signature.required'      => 'A valid digital signature is mandatory to submit your application.',
        ]);

        $signaturePath = $this->saveDigitalSignature($request);
        if (!$signaturePath) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid digital signature on the canvas.'], 422);
        }

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $application = InternshipApplication::create([
            'internship_id'    => $internship?->id,
            'user_id'          => $user?->id,
            'status'           => 'applied',
            'first_name'       => $data['first_name'] ?? ($user?->first_name ?? null),
            'last_name'        => $data['last_name'] ?? ($user?->last_name ?? null),
            'email'            => $data['email'] ?? ($user?->email ?? null),
            'phone'            => $data['phone'] ?? ($user?->phone ?? null),
            'degree'           => $data['degree'] ?? null,
            'graduation_year'  => $data['graduation_year'] ?? null,
            'message'          => $data['message'] ?? null,
            'cover_letter'     => $data['cover_letter'] ?? null,
            'resume_url'       => $resumePath,
            'portfolio_url'    => $data['portfolio_url'] ?? null,
            'github_url'       => $data['github_url'] ?? null,
            'linkedin_url'     => $data['linkedin_url'] ?? null,
            'application_type' => $data['application_type'] ?? ($internship?->title ?? 'Internship Application'),
            'source_page'      => $data['source_page'] ?? 'Dedicated Internship Apply Page',
            'experience_years' => $data['experience_years'] ?? null,
            'current_company'  => $data['current_company'] ?? null,
            'available_from'   => $data['available_from'] ?? null,
            'expected_stipend' => $data['expected_stipend'] ?? null,
            'terms_accepted'   => true,
            'terms_accepted_at'=> now(),
            'terms_version'    => $data['terms_version'] ?? 'v1.0',
            'signature_path'   => $signaturePath,
            'signed_at'        => now(),
            'applied_at'       => now(),
        ]);

        // Audit Trail
        AuditLog::create([
            'user_id'    => $user?->id,
            'action'     => 'internship_application_submitted',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'System',
            'payload'    => [
                'application_id' => $application->id,
                'internship_id'  => $internship?->id,
                'applicant_name' => $application->applicant_name,
                'terms_accepted' => true,
                'terms_version'  => $application->terms_version,
            ],
        ]);

        // Email Notification to Admin (info.blueboxx@gmail.com)
        try {
            Mail::to('info.blueboxx@gmail.com')->send(new AdminNewInternshipApplicationMail($application));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin application notification email delivery failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Internship application submitted successfully!',
            'data'    => [
                'application_id' => $application->id,
                'status'         => $application->status,
                'terms_accepted' => $application->terms_accepted,
                'signed_at'      => $application->signed_at,
            ],
        ], 201);
    }

    /**
     * General application endpoint
     * POST /api/public/internships/apply-general
     */
    public function applyGeneral(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        $data = $request->validate([
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => 'required|email|max:255',
            'phone'            => 'required|string|max:50',
            'degree'           => 'nullable|string|max:255',
            'graduation_year'  => 'nullable|string|max:50',
            'message'          => 'nullable|string|max:5000',
            'cover_letter'     => 'nullable|string|max:5000',
            'resume'           => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'portfolio_url'    => 'nullable|string|max:1000',
            'github_url'       => 'nullable|string|max:1000',
            'linkedin_url'     => 'nullable|string|max:1000',
            'application_type' => 'nullable|string|max:255',
            'source_page'      => 'nullable|string|max:255',
            'internship_id'    => 'nullable|integer',
            'terms_accepted'   => 'required|in:1,true,yes,on',
            'terms_version'    => 'nullable|string|max:50',
            'signature'        => 'required',
        ], [
            'terms_accepted.required' => 'You must agree to the Terms & Conditions before submitting.',
            'signature.required'      => 'A valid digital signature is mandatory to submit your application.',
        ]);

        $signaturePath = $this->saveDigitalSignature($request);
        if (!$signaturePath) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid digital signature.'], 422);
        }

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $application = InternshipApplication::create([
            'internship_id'    => $data['internship_id'] ?? null,
            'user_id'          => $user?->id,
            'status'           => 'applied',
            'first_name'       => $data['first_name'],
            'last_name'        => $data['last_name'] ?? null,
            'email'            => $data['email'],
            'phone'            => $data['phone'],
            'degree'           => $data['degree'] ?? null,
            'graduation_year'  => $data['graduation_year'] ?? null,
            'message'          => $data['message'] ?? null,
            'cover_letter'     => $data['cover_letter'] ?? null,
            'resume_url'       => $resumePath,
            'portfolio_url'    => $data['portfolio_url'] ?? null,
            'github_url'       => $data['github_url'] ?? null,
            'linkedin_url'     => $data['linkedin_url'] ?? null,
            'application_type' => $data['application_type'] ?? 'Fast Track Program Application',
            'source_page'      => $data['source_page'] ?? 'General Internship Form',
            'terms_accepted'   => true,
            'terms_accepted_at'=> now(),
            'terms_version'    => $data['terms_version'] ?? 'v1.0',
            'signature_path'   => $signaturePath,
            'signed_at'        => now(),
            'applied_at'       => now(),
        ]);

        AuditLog::create([
            'user_id'    => $user?->id,
            'action'     => 'internship_general_application_submitted',
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'System',
            'payload'    => [
                'application_id' => $application->id,
                'applicant_name' => $application->applicant_name,
            ],
        ]);

        try {
            Mail::to('info.blueboxx@gmail.com')->send(new AdminNewInternshipApplicationMail($application));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin email delivery failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Application received successfully! Our team will review your submission.',
            'data'    => ['application_id' => $application->id, 'status' => $application->status],
        ], 201);
    }

    /**
     * Download or view official Terms and Conditions PDF document
     * GET /api/public/documents/terms-and-conditions
     */
    public function downloadTermsAndConditions(Request $request, AppointmentLetterService $service)
    {
        $filePath = $service->getTermsAndConditionsPdf();

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Terms & Conditions document not found.'], 404);
        }

        return response()->file($filePath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Blueboxx_Internship_Terms_and_Conditions.pdf"',
        ]);
    }

    /**
     * View/stream signature image safely
     * GET /api/public/internships/applications/{id}/signature
     */
    public function signature(Request $request, $id)
    {
        $app = InternshipApplication::findOrFail($id);
        $user = auth('sanctum')->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            }
        }

        // Authorization: owner or admin
        $isOwner = $user && ($user->id === $app->user_id);
        $isAdmin = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super_admin']);

        if (!$isOwner && !$isAdmin) {
            // For guest submissions, allow viewing during active submission flow if requested
            if ($app->user_id !== null) {
                return response()->json(['success' => false, 'message' => 'Unauthorized signature access.'], 403);
            }
        }

        if (empty($app->signature_path) || !Storage::disk('local')->exists($app->signature_path)) {
            return response()->json(['success' => false, 'message' => 'Signature document not found.'], 404);
        }

        $path = Storage::disk('local')->path($app->signature_path);
        return response()->file($path, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Get my internship applications
     * GET /api/public/internships/my-applications
     */
    public function myApplications(Request $request)
    {
        $applications = InternshipApplication::with(['internship', 'appointmentLetter'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $data = $applications->through(fn($a) => [
            'id'                     => $a->id,
            'internship'             => $a->internship?->title ?? $a->application_type,
            'company'                => $a->internship?->company_name ?? 'Blueboxx DA',
            'status'                 => $a->status,
            'terms_accepted'         => (bool)$a->terms_accepted,
            'terms_version'          => $a->terms_version,
            'signature_url'          => $a->signature_url,
            'rejection_reason'       => $a->rejection_reason,
            'appointment_letter_url' => $a->appointment_letter_url,
            'applied_at'             => $a->applied_at?->format('M d, Y') ?? $a->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => ['current_page' => $data->currentPage(), 'total' => $data->total()],
        ]);
    }
}