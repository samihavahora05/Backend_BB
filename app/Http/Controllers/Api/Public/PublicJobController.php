<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobBookmark;
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
        $query = Job::query()->where('status', 'active');

        // Search
        if ($s = $request->query('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%");
            });
        }

        // Filters
        if ($type = $request->query('job_type')) {
            $query->where('job_type', $type);
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

        $data = $jobs->through(fn($j) => [
            'id'               => $j->id,
            'title'            => $j->title,
            'company_name'     => $j->company_name,
            'company_logo'     => $j->company_logo ? asset('storage/' . $j->company_logo) : null,
            'location'         => $j->location,
            'job_type'         => $j->job_type,
            'experience_level' => $j->experience_level,
            'salary_min'       => $j->hide_salary ? null : $j->salary_min,
            'salary_max'       => $j->hide_salary ? null : $j->salary_max,
            'hide_salary'      => $j->hide_salary,
            'is_featured'      => $j->is_featured,
            'application_deadline' => $j->application_deadline?->format('M d, Y'),
            'posted_at'        => $j->created_at->diffForHumans(),
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
     * Public job detail
     * GET /api/public/jobs/{id}
     */
    public function show(Request $request, $id)
    {
        $job = Job::where('status', 'active')->findOrFail($id);

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

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'company_name'         => $job->company_name,
                'company_logo'         => $job->company_logo ? asset('storage/' . $job->company_logo) : null,
                'company_description'  => $job->company_description ?? null,
                'location'             => $job->location,
                'job_type'             => $job->job_type,
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
                'posted_at'            => $job->created_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * Apply for a job (requires auth + resume upload)
     * POST /api/public/jobs/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $job = Job::where('status', 'active')->findOrFail($id);

        // Check deadline
        if ($job->application_deadline && $job->application_deadline->isPast()) {
            return response()->json(['success' => false, 'message' => 'Application deadline has passed'], 422);
        }

        // Prevent students from applying to jobs
        if ($request->user()->hasRole('student')) {
            return response()->json(['success' => false, 'message' => 'Students can only apply for internships and courses.'], 403);
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
