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
                return [
                    'id' => $job->id,
                    'job_id' => $job->job_id_prefix,
                    'title' => $job->title,
                    'employment_type' => $job->employment_type,
                    'status' => $job->status,
                    'remote_type' => $job->remote_type,
                    'location' => $job->location,
                    'salary_min' => $job->salary_min,
                    'salary_max' => $job->salary_max,
                    'applicants' => $job->applications_count,
                    'posted' => $job->created_at->diffForHumans(),
                    'views' => 0 // Fallback until views tracking is fully merged
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
     * Create a new job posting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'required|string|in:Full-Time,Part-Time,Contract,Internship',
            'experience_level' => 'required|string',
            'remote_type' => 'required|string|in:Remote,Hybrid,Onsite',
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
            'status' => 'required|string|in:draft,active'
        ]);

        $job = new Job();
        $job->company_id = $request->user()->id;
        $job->job_id_prefix = 'JOB-' . date('Y') . '-' . rand(1000, 9999);
        $job->title = $validated['title'];
        $job->department = $validated['department'] ?? null;
        $job->industry = $validated['industry'] ?? null;
        $job->employment_type = $validated['employment_type'];
        $job->experience_level = $validated['experience_level'];
        $job->remote_type = $validated['remote_type'];
        $job->location = $validated['location'] ?? null;
        $job->salary_min = $validated['salary_min'] ?? null;
        $job->salary_max = $validated['salary_max'] ?? null;
        $job->hide_salary = $validated['hide_salary'] ?? false;
        $job->description = $validated['description'];
        
        // JSON casting
        $job->responsibilities = json_encode($validated['responsibilities'] ?? []);
        $job->requirements = json_encode($validated['requirements'] ?? []);
        $job->benefits = json_encode($validated['benefits'] ?? []);
        $job->required_skills = json_encode($validated['required_skills'] ?? []);
        
        $job->vacancies = $validated['vacancies'] ?? 1;
        $job->application_deadline = $validated['application_deadline'] ?? null;
        $job->status = $validated['status']; // Draft or Pending Approval
        
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job posted successfully.',
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
            'employment_type' => 'sometimes|string|in:Full-Time,Part-Time,Contract,Internship',
            'experience_level' => 'sometimes|string',
            'remote_type' => 'sometimes|string|in:Remote,Hybrid,Onsite',
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
            'status' => 'sometimes|string|in:draft,active,closed'
        ]);

        $job->fill($validated);
        
        if ($request->has('responsibilities')) $job->responsibilities = json_encode($validated['responsibilities']);
        if ($request->has('requirements')) $job->requirements = json_encode($validated['requirements']);
        if ($request->has('benefits')) $job->benefits = json_encode($validated['benefits']);
        if ($request->has('required_skills')) $job->required_skills = json_encode($validated['required_skills']);
        
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'data' => $job
        ]);
    }

    /**
     * Update job status (Active/Closed)
     */
    public function updateStatus(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:draft,active,closed,expired',
        ]);

        $job->status = $validated['status'];
        $job->save();

        return response()->json([
            'success' => true,
            'message' => "Job marked as {$validated['status']}.",
            'data' => $job
        ]);
    }

    /**
     * Delete a job posting
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
