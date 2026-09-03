<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobBookmark;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicJobController extends Controller
{
    /**
     * Public job listing with search, filters, and pagination
     * GET /api/public/jobs
     */
    public function index(Request $request)
    {
        $query = Job::query()->with('company.companyProfile');

        // Search
        if ($s = $request->query('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%")
                  ->orWhere('department', 'like', "%{$s}%")
                  ->orWhere('industry', 'like', "%{$s}%")
                  ->orWhereHas('company.companyProfile', function($query) use ($s) {
                      $query->where('company_name', 'like', "%{$s}%");
                  });
            });
        }

        // Filters
        if ($type = $request->query('job_type')) {
            $query->where('employment_type', $type);
        }
        if ($loc = $request->query('location')) {
            $query->where('location', 'like', "%{$loc}%");
        }
        if ($exp = $request->query('experience_level')) {
            $query->where('experience_level', $exp);
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Salary range
        if ($min = $request->query('min_salary')) {
            $query->where('salary_min', '>=', $min);
        }
        if ($max = $request->query('max_salary')) {
            $query->where('salary_max', '<=', $max);
        }

        // Sort
        $sort = $request->query('sort', 'newest');
        if ($sort === 'newest') $query->latest();
        elseif ($sort === 'salary_high') $query->orderByDesc('salary_max');

        $perPage = min((int)$request->query('per_page', 15), 50);
        $jobs = $query->paginate($perPage);

        $data = $jobs->through(function($j) {
            $loc = $j->location ?: ($j->remote_type ?: 'On-site');
            $remoteType = $j->remote_type ?: ($loc === 'Remote' ? 'Remote' : 'On-site');

            $salaryFormatted = 'Best in Industry';
            if (!empty($j->display_salary)) {
                $salaryFormatted = $j->display_salary;
                if (is_numeric(str_replace(',', '', $salaryFormatted))) {
                    $salaryFormatted = '₹' . number_format((float)str_replace(',', '', $salaryFormatted)) . '/mo';
                }
            } elseif (!$j->hide_salary) {
                if ($j->salary_min && $j->salary_max) {
                    $salaryFormatted = ($j->salary_min >= 100000)
                        ? ('₹' . round($j->salary_min/100000, 1) . ' - ₹' . round($j->salary_max/100000, 1) . ' LPA')
                        : ('₹' . number_format($j->salary_min) . ($j->salary_min != $j->salary_max ? ' - ₹' . number_format($j->salary_max) : '') . '/mo');
                } elseif ($j->salary_min) {
                    $salaryFormatted = ($j->salary_min >= 100000)
                        ? ('₹' . round($j->salary_min/100000, 1) . ' LPA+')
                        : ('₹' . number_format($j->salary_min) . '/mo');
                }
            }

            return [
                'id'               => $j->id,
                'job_id_prefix'    => $j->job_id_prefix,
                'title'            => $j->title,
                'company_name'     => $j->company_name ?: ($j->company?->companyProfile?->company_name ?? 'BlueBoxx Partner'),
                'company_logo'     => $j->company_logo ? (str_starts_with($j->company_logo, 'http') ? $j->company_logo : asset('storage/' . $j->company_logo)) : null,
                'location'         => $loc,
                'state'            => $j->state,
                'area'             => $j->area,
                'remote_type'      => $remoteType,
                'workplace_type'   => $remoteType,
                'job_type'         => $j->employment_type ?? 'Full-Time',
                'employment_type'  => $j->employment_type ?? 'Full-Time',
                'experience_level' => $j->experience_level,
                'education_qualification' => $j->education_qualification,
                'salary'           => $salaryFormatted,
                'salary_min'       => $j->hide_salary ? null : $j->salary_min,
                'salary_max'       => $j->hide_salary ? null : $j->salary_max,
                'hide_salary'      => $j->hide_salary,
                'vacancies'        => $j->vacancies ?? 1,
                'shift_timings'    => $j->shift_timings,
                'industry'         => $j->industry,
                'department'       => $j->department,
                'required_skills'  => is_array($j->required_skills) ? $j->required_skills : (is_string($j->required_skills) ? json_decode($j->required_skills, true) : []),
                'contact_name'     => $j->contact_name,
                'contact_phone'    => $j->contact_phone,
                'is_featured'      => (bool)$j->is_featured,
                'application_deadline' => $j->application_deadline ? Carbon::parse($j->application_deadline)->format('M d, Y') : null,
                'posted_at'        => $j->created_at ? $j->created_at->diffForHumans() : 'Recently',
            ];
        });

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
     * Public job detail
     * GET /api/public/jobs/{id}
     */
    public function show(Request $request, $id)
    {
        $job = Job::with('company.companyProfile')->findOrFail($id);

        // Track view
        \DB::table('job_views')->insertOrIgnore([
            'job_id'     => $job->id,
            'ip_address' => $request->ip(),
            'user_id'    => auth('sanctum')->id(),
        ]);

        $isBookmarked = false;
        $hasApplied = false;
        if ($request->user()) {
            $isBookmarked = JobBookmark::where('job_id', $job->id)->where('user_id', $request->user()->id)->exists();
            $hasApplied = JobApplication::where('job_id', $job->id)->where('user_id', $request->user()->id)->exists();
        }

        $loc = $job->location ?: ($job->remote_type ?: 'On-site');
        $remoteType = $job->remote_type ?: ($loc === 'Remote' ? 'Remote' : 'On-site');

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'company_name'         => $job->company_name,
                'company_logo'         => $job->company_logo ? asset('storage/' . $job->company_logo) : null,
                'company_description'  => $job->company_description ?? null,
                'location'             => $loc,
                'remote_type'          => $remoteType,
                'workplace_type'       => $remoteType,
                'job_type'             => $job->employment_type ?? 'Full-Time',
                'employment_type'      => $job->employment_type ?? 'Full-Time',
                'experience_level'     => $job->experience_level,
                'education_level'      => $job->education_level ?? null,
                'salary_min'           => $job->hide_salary ? null : $job->salary_min,
                'salary_max'           => $job->hide_salary ? null : $job->salary_max,
                'hide_salary'          => $job->hide_salary,
                'description'          => $job->description,
                'responsibilities'     => $job->responsibilities ?? [],
                'requirements'         => $job->requirements ?? [],
                'benefits'             => $job->benefits ?? [],
                'required_skills'      => $job->required_skills ?? [],
                'application_deadline' => $job->application_deadline?->format('M d, Y'),
                'is_featured'          => $job->is_featured,
                'application_count'    => $job->applications()->count(),
                'is_bookmarked'        => $isBookmarked,
                'has_applied'          => $hasApplied,
                'posted_at'            => $job->created_at ? $job->created_at->diffForHumans() : 'Recently',
            ]
        ]);
    }

    /**
     * Apply for a job (requires auth + resume upload)
     * POST /api/public/jobs/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        // Check deadline
        if ($job->application_deadline && $job->application_deadline->isPast()) {
            return response()->json(['success' => false, 'message' => 'Application deadline has passed'], 422);
        }



        // Check for duplicate application
        $alreadyApplied = JobApplication::where('job_id', $job->id)
            ->where('user_id', $request->user()->id)
            ->exists();
        if ($alreadyApplied) {
            return response()->json(['success' => false, 'message' => 'You have already applied for this job'], 422);
        }

        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
            'resume'       => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $application = JobApplication::create([
            'job_id'       => $job->id,
            'user_id'      => $request->user()->id,
            'status'       => 'applied',
            'cover_letter' => $data['cover_letter'] ?? null,
            'resume_path'  => $resumePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'data'    => ['application_id' => $application->id, 'status' => $application->status],
        ], 201);
    }

    /**
     * Toggle bookmark on a job
     * POST /api/public/jobs/{id}/bookmark
     */
    public function toggleBookmark(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $existing = JobBookmark::where('job_id', $job->id)->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'bookmarked' => false]);
        }

        JobBookmark::create(['job_id' => $job->id, 'user_id' => $request->user()->id]);
        return response()->json(['success' => true, 'bookmarked' => true]);
    }

    /**
     * Get my job applications
     * GET /api/public/jobs/my-applications
     */
    public function myApplications(Request $request)
    {
        $applications = JobApplication::with(['job'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $data = $applications->through(fn($a) => [
            'id'         => $a->id,
            'job_title'  => $a->job?->title,
            'company'    => $a->job?->company_name,
            'status'     => $a->status,
            'applied_at' => $a->applied_at?->format('M d, Y') ?? $a->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => ['current_page' => $data->currentPage(), 'total' => $data->total()],
        ]);
    }

    /**
     * Get my bookmarked jobs
     * GET /api/public/jobs/bookmarks
     */
    public function bookmarks(Request $request)
    {
        $bookmarks = JobBookmark::with(['job'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn($b) => [
                'id'           => $b->job?->id,
                'title'        => $b->job?->title,
                'company_name' => $b->job?->company_name,
                'location'     => $b->job?->location,
                'job_type'     => $b->job?->job_type,
                'bookmarked_at'=> $b->created_at->format('M d, Y'),
            ]);

        return response()->json(['success' => true, 'data' => $bookmarks]);
    }
}
