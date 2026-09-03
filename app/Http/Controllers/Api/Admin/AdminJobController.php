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
            'active_jobs'        => Job::whereIn('status', ['active', 'open', 'published'])->count(),
            'pending_jobs'       => Job::whereIn('status', ['pending', 'pending_approval'])->count(),
            'expired_jobs'       => Job::whereIn('status', ['expired', 'closed'])->count(),
            'total_applications' => JobApplication::count(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Job::with(['company.companyProfile'])
            ->withCount(['applications', 'views']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && strtolower($request->status) !== 'all') {
            $st = strtolower($request->status);
            if (in_array($st, ['pending', 'pending_approval'])) {
                $query->whereIn('status', ['pending', 'pending_approval']);
            } else {
                $query->where('status', $st);
            }
        }

        $jobs = $query->latest()->paginate($request->input('per_page', 15));

        // Transform so frontend always gets a consistent shape
        $jobs->getCollection()->transform(function ($job) {
            $compName = $job->company_name
                ?: ($job->company?->companyProfile?->company_name)
                ?: ($job->company?->name)
                ?: (trim(($job->company?->first_name ?? '') . ' ' . ($job->company?->last_name ?? '')))
                ?: 'Company';

            return [
                'id'                 => $job->id,
                'title'              => $job->title,
                'job_id_prefix'      => $job->job_id_prefix ?: ('JOB-' . $job->created_at->format('Y') . '-' . $job->id),
                'company'            => [
                    'name' => $compName,
                    'id' => $job->company_id,
                ],
                'employment_type'    => $job->employment_type ?? $job->job_type ?? 'Full-time',
                'location'           => $job->location ?? 'Remote',
                'applications_count' => $job->applications_count ?? 0,
                'views_count'        => $job->views_count ?? 0,
                'status'             => $job->status ?? 'pending_approval',
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

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:jobs,id',
        ]);
        Job::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Jobs deleted successfully']);
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

    public function updateStatus(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $status = strtolower($request->input('status', 'active'));
        $job->status = $status;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => "Job marked as {$status}",
            'data' => $job
        ]);
    }

    /**
     * Download a sample CSV template for Job Import
     */
    public function sampleCsv()
    {
        $headers = [
            'title', 'company', 'department', 'industry', 
            'employment_type', 'experience_level', 'remote_type', 'location', 
            'salary_min', 'salary_max', 'vacancies', 'required_skills',
            'description', 'requirements', 'responsibilities', 'benefits'
        ];

        $sampleRow = [
            'Senior Full Stack Developer',
            'TechCorp Global',
            'Engineering',
            'IT & Software',
            'Full-Time',
            'Senior',
            'Remote',
            'Bangalore, India',
            '1200000',
            '1800000',
            '3',
            'React, Next.js, Node.js, TypeScript, PostgreSQL',
            'We are looking for an experienced Full Stack Developer to build scalable web apps.',
            '5+ years experience with React and Node.js; Strong TypeScript and cloud knowledge',
            'Design and architect microservices; Write clean reusable code; Mentor junior developers',
            'Health Insurance, Annual Bonus, Flexible Working Hours, Learning Allowance'
        ];

        $sampleRow2 = [
            'UI/UX Product Designer',
            'DesignHub Studio',
            'Design',
            'Media & Design',
            'Full-Time',
            'Mid-Level',
            'Hybrid',
            'Mumbai, India',
            '600000',
            '900000',
            '2',
            'Figma, Design Systems, Wireframing, User Research, Adobe XD',
            'Create delightful user experiences and high-fidelity prototypes for client platforms.',
            '3+ years product design experience with a strong online portfolio',
            'Conduct user interviews, design wireframes and prototypes, collaborate with engineering',
            'Gym Membership, Remote Fridays, Stock Options'
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        fputcsv($csv, $sampleRow);
        fputcsv($csv, $sampleRow2);

        rewind($csv);
        $csvData = stream_get_contents($csv);
        fclose($csv);

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="jobs-sample-template.csv"');
    }

    /**
     * Import jobs from uploaded CSV file
     * POST /api/admin/jobs/import
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:csv,txt',
            'csv_file' => 'nullable|file|mimes:csv,txt',
        ]);

        $file = $request->file('file') ?? $request->file('csv_file');

        if (!$file && !$request->has('csv_data')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid CSV file.'
            ], 422);
        }

        $filePath = $file ? $file->getRealPath() : null;
        $csvString = $request->input('csv_data');

        if ($filePath) {
            $handle = fopen($filePath, 'r');
        } elseif ($csvString) {
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $csvString);
            rewind($handle);
        } else {
            return response()->json(['success' => false, 'message' => 'No CSV content found.'], 422);
        }

        // Default Company / Admin
        $defaultCompany = \App\Models\User::role('company')->first() 
            ?? \App\Models\User::role('admin')->first() 
            ?? \App\Models\User::role('super_admin')->first()
            ?? auth()->user();

        $defaultCompanyId = $defaultCompany ? $defaultCompany->id : 1;

        // Read and normalize headers
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'CSV file is empty.'], 422);
        }

        // Strip UTF-8 BOM if present
        $rawHeaders[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rawHeaders[0]);

        $headerMap = [];
        foreach ($rawHeaders as $idx => $h) {
            $cleaned = strtolower(trim(str_replace([' ', '_', '-'], '', $h)));
            $headerMap[$cleaned] = $idx;
        }

        $imported = 0;
        $errors = [];
        $rowNum = 1;

        $getVal = function ($row, $keys, $default = null) use ($headerMap) {
            foreach ((array)$keys as $k) {
                $cleaned = strtolower(trim(str_replace([' ', '_', '-'], '', $k)));
                if (isset($headerMap[$cleaned]) && isset($row[$headerMap[$cleaned]])) {
                    $val = trim($row[$headerMap[$cleaned]]);
                    if ($val !== '') return $val;
                }
            }
            return $default;
        };

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Skip empty lines
                if (count(array_filter($row)) === 0) continue;

                $title = $getVal($row, ['title', 'jobtitle', 'role', 'position', 'name']);
                if (empty($title)) {
                    $errors[] = "Row {$rowNum}: Missing Job Title.";
                    continue;
                }

                $companyName = $getVal($row, ['company', 'companyname', 'employer', 'organization'], 'BlueBoxx Partner');
                $department = $getVal($row, ['department', 'dept', 'category'], 'Engineering');
                $industry = $getVal($row, ['industry', 'sector'], 'Technology');
                $employmentType = $getVal($row, ['employmenttype', 'jobtype', 'type'], 'Full-Time');
                $experienceLevel = $getVal($row, ['experiencelevel', 'experience', 'explevel'], 'Entry-Level');
                $remoteType = $getVal($row, ['remotetype', 'workplacetype', 'workplace', 'mode'], 'Onsite');
                $location = $getVal($row, ['location', 'city', 'joblocation'], 'India');
                $salaryMin = $getVal($row, ['salarymin', 'minsalary', 'salary', 'packagesalary'], null);
                $salaryMax = $getVal($row, ['salarymax', 'maxsalary'], null);
                $vacancies = (int)$getVal($row, ['vacancies', 'openings', 'positions'], 1);
                $description = $getVal($row, ['description', 'jobdescription', 'desc'], "Join our team as a {$title}.");
                $status = strtolower($getVal($row, ['status'], 'active'));
                if (!in_array($status, ['active', 'draft', 'closed', 'expired'])) {
                    $status = 'active';
                }

                // Parse arrays (comma/newline/bullet separated)
                $parseList = function ($text) {
                    if (!$text) return [];
                    if (is_array($text)) return $text;
                    $items = preg_split('/[,\n\r;|]+/', $text);
                    return array_values(array_filter(array_map('trim', $items)));
                };

                $skills = $parseList($getVal($row, ['requiredskills', 'skills', 'keyskills'], ''));
                $requirements = $parseList($getVal($row, ['requirements', 'qualifications'], ''));
                $responsibilities = $parseList($getVal($row, ['responsibilities', 'rolesresponsibilities', 'duties'], ''));
                $benefits = $parseList($getVal($row, ['benefits', 'perks'], ''));

                $jobIdPrefix = 'JOB-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

                Job::create([
                    'job_id_prefix'      => $jobIdPrefix,
                    'company_id'         => $defaultCompanyId,
                    'title'              => $title,
                    'department'         => $department,
                    'industry'           => $industry,
                    'employment_type'    => $employmentType,
                    'experience_level'   => $experienceLevel,
                    'remote_type'        => $remoteType,
                    'location'           => $location,
                    'salary_min'         => is_numeric($salaryMin) ? (float)$salaryMin : null,
                    'salary_max'         => is_numeric($salaryMax) ? (float)$salaryMax : null,
                    'hide_salary'        => false,
                    'description'        => $description,
                    'responsibilities'   => $responsibilities,
                    'requirements'       => $requirements,
                    'benefits'           => $benefits,
                    'required_skills'    => $skills,
                    'vacancies'          => max(1, $vacancies),
                    'application_deadline' => now()->addDays(45),
                    'is_featured'        => $imported < 5,
                    'status'             => $status,
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            return response()->json([
                'success'        => true,
                'message'        => "Successfully imported {$imported} jobs.",
                'imported_count' => $imported,
                'errors'         => $errors,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'CSV Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
