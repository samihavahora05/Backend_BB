<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobController extends Controller
{
    public function dashboardMetrics()
    {
        return response()->json([
            'active_jobs'        => Job::where('status', 'active')->count(),
            'pending_jobs'       => Job::where('status', 'pending_approval')->count(),
            'expired_jobs'       => Job::where('status', 'expired')->count(),
            'total_applications' => JobApplication::count(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Job::with(['company'])
            ->withCount(['applications', 'views']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', strtolower($request->status));
        }

        $jobs = $query->latest()->paginate($request->input('per_page', 15));

        // Transform so frontend always gets a consistent shape
        $jobs->getCollection()->transform(function ($job) {
            return [
                'id'                 => $job->id,
                'title'              => $job->title,
                'job_id_prefix'      => 'JOB-' . $job->created_at->format('Y') . '-' . $job->id,
                'company'            => [
                    'name' => $job->company
                        ? trim(($job->company->first_name ?? '') . ' ' . ($job->company->last_name ?? ''))
                        : 'BlueBoxx',
                    'id' => $job->company_id,
                ],
                'employment_type'    => $job->employment_type ?? $job->job_type ?? 'Full-time',
                'location'           => $job->location ?? 'Remote',
                'applications_count' => $job->applications_count ?? 0,
                'views_count'        => $job->views_count ?? 0,
                'status'             => $job->status ?? 'active',
                'created_at'         => $job->created_at,
            ];
        });

        return response()->json($jobs);
    }

    public function show($id)
    {
        $job = Job::with(['company', 'applications'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['company_id'] = auth()->id() ?? 1;
        $data['job_id_prefix'] = $request->input('jobId') ?: 'JOB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        $data['status'] = strtolower($data['status'] ?? 'active');
        $data['experience_level'] = $data['experience_level'] ?? 'Entry-Level';
        $data['employment_type'] = $data['employment_type'] ?? 'Full-Time';
        $data['remote_type'] = $data['remote_type'] ?? 'Onsite';

        // Map frontend fields to DB
        $data['vacancies'] = $data['openings'] ?? 1;
        $data['is_featured'] = $data['featuredJob'] ?? false;
        $data['hide_salary'] = $data['hide_salary'] ?? false;
        
        if (isset($data['requiredSkills'])) $data['required_skills'] = array_filter(array_map('trim', explode(',', $data['requiredSkills'])));
        if (isset($data['responsibilities'])) $data['responsibilities'] = array_filter(array_map('trim', explode("\n", $data['responsibilities'])));
        if (isset($data['benefits'])) $data['benefits'] = array_filter(array_map('trim', explode("\n", $data['benefits'])));
        
        // Remove frontend-only keys to prevent SQL errors if they aren't in guarded
        unset($data['jobId'], $data['openings'], $data['featuredJob'], $data['requiredSkills'], $data['type'], $data['workMode'], $data['minSalary'], $data['maxSalary'], $data['applicationDeadline'], $data['companyLogo'], $data['companyName'], $data['companyWebsite'], $data['recruiterName'], $data['recruiterEmail'], $data['recruiterPhone'], $data['qualification'], $data['experience'], $data['category'], $data['salaryType'], $data['applicationStartDate'], $data['joiningDate'], $data['companyBanner'], $data['videoUrl'], $data['urgentHiring']);

        $job = Job::create($data);

        return response()->json(['success' => true, 'data' => $job, 'message' => 'Job created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $data = $request->all();
        $data['status'] = strtolower($data['status'] ?? $job->status);

        if (isset($data['openings'])) $data['vacancies'] = $data['openings'];
        if (isset($data['featuredJob'])) $data['is_featured'] = $data['featuredJob'];
        if (isset($data['requiredSkills']) && is_string($data['requiredSkills'])) $data['required_skills'] = array_filter(array_map('trim', explode(',', $data['requiredSkills'])));
        if (isset($data['responsibilities']) && is_string($data['responsibilities'])) $data['responsibilities'] = array_filter(array_map('trim', explode("\n", $data['responsibilities'])));
        if (isset($data['benefits']) && is_string($data['benefits'])) $data['benefits'] = array_filter(array_map('trim', explode("\n", $data['benefits'])));
        
        unset($data['jobId'], $data['openings'], $data['featuredJob'], $data['requiredSkills'], $data['type'], $data['workMode'], $data['minSalary'], $data['maxSalary'], $data['applicationDeadline'], $data['companyLogo'], $data['companyName'], $data['companyWebsite'], $data['recruiterName'], $data['recruiterEmail'], $data['recruiterPhone'], $data['qualification'], $data['experience'], $data['category'], $data['salaryType'], $data['applicationStartDate'], $data['joiningDate'], $data['companyBanner'], $data['videoUrl'], $data['urgentHiring']);

        $job->update($data);

        return response()->json(['success' => true, 'data' => $job, 'message' => 'Job updated successfully']);
    }

    public function destroy($id)
    {
        Job::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Job deleted successfully']);
    }

    /**
     * Export jobs as CSV
     */
    public function export()
    {
        $jobs = Job::with('company')->latest()->get();
        $headers = [
            'ID', 'Job ID', 'Title', 'Company', 'Department', 'Industry', 
            'Employment Type', 'Experience Level', 'Remote Type', 'Location', 
            'Min Salary', 'Max Salary', 'Vacancies', 'Deadline', 'Status', 
            'Applications', 'Views', 'Featured', 'Created'
        ];
        
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        
        foreach ($jobs as $j) {
            $company = $j->company ? trim(($j->company->first_name ?? '') . ' ' . ($j->company->last_name ?? '')) : 'BlueBoxx';
            if (empty($company)) $company = 'BlueBoxx';
            
            fputcsv($csv, [
                $j->id,
                $j->job_id_prefix,
                $j->title,
                $company,
                $j->department,
                $j->industry,
                $j->employment_type,
                $j->experience_level,
                $j->remote_type,
                $j->location,
                $j->salary_min,
                $j->salary_max,
                $j->vacancies,
                $j->application_deadline ? \Carbon\Carbon::parse($j->application_deadline)->format('Y-m-d') : '',
                $j->status,
                $j->applications()->count(),
                $j->views_count ?? 0,
                $j->is_featured ? 'Yes' : 'No',
                $j->created_at ? $j->created_at->format('Y-m-d H:i') : ''
            ]);
        }
        
        rewind($csv);
        $csvData = stream_get_contents($csv);
        fclose($csv);
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="jobs-export.csv"');
    }
}
