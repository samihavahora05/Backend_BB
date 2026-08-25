<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Str;

class CompanyJobController extends Controller
{
    /**
     * Get all jobs for the company
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $jobs = Job::where('company_id', $companyId)
            ->latest()
            ->withCount('applications')
            ->get()
            ->map(function($job) {
                $statusNormalized = strtolower($job->status ?? 'pending_approval');
                $displayStatus = 'Pending Approval';
                if (in_array($statusNormalized, ['active', 'open', 'published'])) {
                    $displayStatus = 'Active';
                } elseif (in_array($statusNormalized, ['draft'])) {
                    $displayStatus = 'Draft';
                } elseif (in_array($statusNormalized, ['rejected'])) {
                    $displayStatus = 'Rejected';
                } elseif (in_array($statusNormalized, ['closed', 'expired'])) {
                    $displayStatus = 'Closed';
                }

                $salaryFormatted = 'Competitive';
                if ($job->salary_min && $job->salary_max) {
                    $salaryFormatted = '₹' . ($job->salary_min >= 100000 ? round($job->salary_min/100000, 1) . ' - ₹' . round($job->salary_max/100000, 1) . ' LPA' : $job->salary_min . ' - ' . $job->salary_max);
                } elseif ($job->salary_min) {
                    $salaryFormatted = '₹' . ($job->salary_min >= 100000 ? round($job->salary_min/100000, 1) . ' LPA' : $job->salary_min);
                }

                $remoteType = $job->remote_type ?: ($job->location === 'Remote' ? 'Remote' : 'On-site');
                $location = $job->location ?: $remoteType;

                return [
                    'id' => $job->id,
                    'job_id' => $job->job_id_prefix ?: ('JOB-' . date('Y') . '-' . $job->id),
                    'title' => $job->title,
                    'category' => $job->employment_type ?? 'Full-Time',
                    'employment_type' => $job->employment_type ?? 'Full-Time',
                    'status' => $displayStatus,
                    'raw_status' => $job->status,
                    'type' => $remoteType,
                    'remote_type' => $remoteType,
                    'location' => $location,
                    'salary' => $salaryFormatted,
                    'salary_min' => $job->salary_min,
                    'salary_max' => $job->salary_max,
                    'applicants' => $job->applications_count,
                    'posted' => $job->created_at ? $job->created_at->diffForHumans() : 'Recently',
                    'views' => $job->views_count ?? 0,
                    'created_at' => $job->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Show a single job details
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->id;
        
        $job = Job::where('company_id', $companyId)
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }

    /**
     * Create a new job posting (Defaults to pending_approval for Admin review)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'required|string',
            'experience_level' => 'nullable|string',
            'remote_type' => 'nullable|string',
            'location' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'hide_salary' => 'nullable|boolean',
            'description' => 'required|string',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'required_skills' => 'nullable|array',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        $status = $request->input('status', 'pending_approval');
        if (!in_array(strtolower($status), ['draft', 'pending', 'pending_approval'])) {
            $status = 'pending_approval'; // Enforce admin approval requirement
        }

        $remoteType = $validated['remote_type'] ?? 'Onsite';
        $location = !empty($validated['location']) ? $validated['location'] : ($remoteType === 'Remote' ? 'Remote' : 'On-site');

        $job = new Job();
        $job->company_id = $request->user()->id;
        $job->job_id_prefix = 'JOB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        $job->title = $validated['title'];
        $job->department = $validated['department'] ?? 'Engineering';
        $job->industry = $validated['industry'] ?? 'Technology';
        $job->employment_type = $validated['employment_type'];
        $job->experience_level = $validated['experience_level'] ?? 'Entry Level';
        $job->remote_type = $remoteType;
        $job->location = $location;
        $job->salary_min = $validated['salary_min'] ?? null;
        $job->salary_max = $validated['salary_max'] ?? null;
        $job->hide_salary = $validated['hide_salary'] ?? false;
        $job->description = $validated['description'];
        
        $job->responsibilities = $validated['responsibilities'] ?? [];
        $job->requirements = $validated['requirements'] ?? [];
        $job->benefits = $validated['benefits'] ?? [];
        $job->required_skills = $validated['required_skills'] ?? [];
        
        $job->vacancies = $validated['vacancies'] ?? 1;
        $job->application_deadline = $validated['application_deadline'] ?? now()->addDays(30)->toDateString();
        $job->status = $status;
        
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job submitted successfully and is pending Admin approval.',
            'data' => $job
        ], 201);
    }

    /**
     * Update an existing job
     */
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'department' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'sometimes|string',
            'experience_level' => 'nullable|string',
            'remote_type' => 'nullable|string',
            'location' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'hide_salary' => 'nullable|boolean',
            'description' => 'sometimes|string',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'required_skills' => 'nullable|array',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        $job->fill($validated);
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'data' => $job
        ]);
    }

    /**
     * Update status (e.g. close, draft)
     */
    public function updateStatus(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)->findOrFail($id);

        $status = strtolower($request->input('status', 'closed'));
        $job->status = $status;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job status updated to ' . $status,
            'data' => $job
        ]);
    }

    /**
     * Delete a job
     */
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)->findOrFail($id);
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.'
        ]);
    }
}
