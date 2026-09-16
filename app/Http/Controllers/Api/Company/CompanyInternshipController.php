<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;

class CompanyInternshipController extends Controller
{
    /**
     * Get all internships for the company (Strictly type = internship)
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $query = Job::where('company_id', $companyId)
            ->internships()
            ->latest()
            ->withCount('applications');

        // Optional server-side search isolation
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Optional server-side status filter
        if ($filter = $request->query('filter')) {
            if ($filter === 'active') {
                $query->whereIn('status', ['active', 'open', 'published', 'pending', 'pending_approval', 'draft']);
            } elseif ($filter === 'closed') {
                $query->whereIn('status', ['closed', 'expired', 'rejected']);
            }
        }

        $internships = $query->get()->map(function($job) {
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

            $stipendFormatted = 'Unpaid';
            if ($job->salary_min) {
                $stipendFormatted = '₹' . number_format($job->salary_min) . '/mo';
            }

            return [
                'id' => $job->id,
                'job_id' => $job->job_id_prefix ?: ('INT-' . date('Y') . '-' . $job->id),
                'title' => $job->title,
                'department' => $job->department,
                'category' => 'Internship',
                'employment_type' => 'Internship',
                'mode' => $job->remote_type ?: 'On-site',
                'remote_type' => $job->remote_type ?: 'On-site',
                'location' => $job->location ?: ($job->remote_type ?: 'On-site'),
                'duration' => $job->duration ?? '3-6 Months',
                'stipend' => $stipendFormatted,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'status' => $displayStatus,
                'raw_status' => $job->status,
                'type' => 'internship',
                'posting_type' => 'internship',
                'applicants' => $job->applications_count,
                'views' => $job->views_count ?? 0,
                'posted' => $job->created_at ? $job->created_at->diffForHumans() : 'Recently',
                'created_at' => $job->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $internships
        ]);
    }

    /**
     * Show a single internship details
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->id;
        
        $internship = Job::where('company_id', $companyId)
            ->internships()
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $internship
        ]);
    }

    /**
     * Create a new internship posting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'remote_type' => 'nullable|string',
            'location' => 'nullable|string',
            'stipend' => 'nullable|numeric',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
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
            $status = 'pending_approval';
        }

        $remoteType = $validated['remote_type'] ?? 'Onsite';
        $location = !empty($validated['location']) ? $validated['location'] : ($remoteType === 'Remote' ? 'Remote' : 'On-site');
        $stipend = $validated['stipend'] ?? $validated['salary_min'] ?? null;

        $internship = new Job();
        $internship->company_id = $request->user()->id;
        $internship->type = 'internship';
        $internship->job_id_prefix = 'INT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        $internship->title = $validated['title'];
        $internship->department = $validated['department'] ?? 'General';
        $internship->industry = $validated['industry'] ?? 'Technology';
        $internship->employment_type = 'Internship';
        $internship->experience_level = 'Internship / Entry Level';
        $internship->remote_type = $remoteType;
        $internship->location = $location;
        $internship->salary_min = $stipend;
        $internship->salary_max = $validated['salary_max'] ?? $stipend;
        $internship->hide_salary = false;
        $internship->description = $validated['description'];
        
        $internship->responsibilities = $validated['responsibilities'] ?? [];
        $internship->requirements = $validated['requirements'] ?? [];
        $internship->benefits = $validated['benefits'] ?? [];
        $internship->required_skills = $validated['required_skills'] ?? [];
        
        $internship->vacancies = $validated['vacancies'] ?? 1;
        $internship->application_deadline = $validated['application_deadline'] ?? now()->addDays(30)->toDateString();
        $internship->status = $status;
        
        $internship->save();

        return response()->json([
            'success' => true,
            'message' => 'Internship submitted successfully and is pending Admin approval.',
            'data' => $internship
        ], 201);
    }

    /**
     * Update an internship
     */
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $internship = Job::where('company_id', $companyId)->internships()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'department' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'remote_type' => 'nullable|string',
            'location' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'description' => 'sometimes|string',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'required_skills' => 'nullable|array',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        $internship->fill($validated);
        $internship->type = 'internship';
        $internship->employment_type = 'Internship';
        $internship->save();

        return response()->json([
            'success' => true,
            'message' => 'Internship updated successfully.',
            'data' => $internship
        ]);
    }
    
    /**
     * Update status for an internship
     */
    public function updateStatus(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)
                  ->internships()
                  ->findOrFail($id);
                  
        $validated = $request->validate([
            'status' => 'required|string|in:draft,active,closed,pending_approval'
        ]);
        
        $job->status = strtolower($validated['status']);
        $job->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Internship status updated to ' . $job->status,
            'data' => $job
        ]);
    }
    
    /**
     * Delete an internship
     */
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)
                  ->internships()
                  ->findOrFail($id);
                  
        $job->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Internship deleted successfully.'
        ]);
    }
}
