<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use Illuminate\Http\Request;

class PublicInternshipController extends Controller
{
    /**
     * Public internship listing with search and filters
     * GET /api/public/internships
     */
    public function index(Request $request)
    {
        $query = Internship::query()->with('company.companyProfile')->whereIn('status', ['open', 'Open']);

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
            $query->where('mode', $type); // Remote, On-site, Hybrid
        }
        if ($domain = $request->query('domain')) {
            $query->where(function($q) use ($domain) {
                $q->where('department', 'like', "%{$domain}%")
                  ->orWhere('title', 'like', "%{$domain}%");
            });
        }
        if ($duration = $request->query('duration')) {
            // Normalize "6 Months" / "1 Year" style filter labels into a month count
            // and match against duration_months (reliable, numeric) instead of only
            // fuzzy-matching the free-text `duration` column, which is inconsistently
            // populated across seeders/imports and caused this filter to silently
            // include/exclude the wrong rows.
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
            $appliedInternshipIds = \App\Models\InternshipApplication::where('user_id', $user->id)
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
        $internship = Internship::with('company.companyProfile')->whereIn('status', ['open', 'Open'])->findOrFail($id);

        $hasApplied = false;
        $isBookmarked = false;
        if ($request->user()) {
            $hasApplied = InternshipApplication::where('internship_id', $internship->id)
                ->where('user_id', $request->user()->id)
                ->exists();
            $isBookmarked = \App\Models\SavedInternship::where('internship_id', $internship->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data'    => array_merge($internship->toArray(), [
                'company_logo' => $internship->company_logo ? \App\Support\StorageHelper::url($internship->company_logo) : null,
                'has_applied'  => $hasApplied,
                'is_bookmarked'=> $isBookmarked,
                'posted_at'    => $internship->created_at->diffForHumans(),
            ])
        ]);
    }

    /**
     * Apply for an internship (requires auth)
     * POST /api/public/internships/{id}/apply
     */
    /**
     * Apply for an internship (supports auth or guest)
     * POST /api/public/internships/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $internship = Internship::whereIn('status', ['open', 'Open', 'active', 'published'])->find($id);

        $user = $request->user();
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
        ]);

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
            'source_page'      => $data['source_page'] ?? 'Internship Page',
            'experience_years' => $data['experience_years'] ?? null,
            'current_company'  => $data['current_company'] ?? null,
            'available_from'   => $data['available_from'] ?? null,
            'expected_stipend' => $data['expected_stipend'] ?? null,
            'applied_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Internship application submitted successfully!',
            'data'    => ['application_id' => $application->id, 'status' => $application->status],
        ], 201);
    }

    /**
     * General application endpoint for fast-track forms, scholarships, and general inquiries
     * POST /api/public/internships/apply-general
     */
    public function applyGeneral(Request $request)
    {
        $user = $request->user();
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
        ]);

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
            'applied_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application received successfully! Our team will contact you shortly.',
            'data'    => ['application_id' => $application->id, 'status' => $application->status],
        ], 201);
    }

    /**
     * Get my internship applications
     * GET /api/public/internships/my-applications
     */
    public function myApplications(Request $request)
    {
        $applications = InternshipApplication::with(['internship'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $data = $applications->through(fn($a) => [
            'id'             => $a->id,
            'internship'     => $a->internship?->title ?? $a->application_type,
            'company'        => $a->internship?->company_name ?? 'Blueboxx DA',
            'status'         => $a->status,
            'applied_at'     => $a->applied_at?->format('M d, Y') ?? $a->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => ['current_page' => $data->currentPage(), 'total' => $data->total()],
        ]);
    }
}
