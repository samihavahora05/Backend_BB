<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternshipRequest;
use App\Repositories\Contracts\InternshipRepositoryInterface;
use App\Services\InternshipService;
use Illuminate\Http\Request;

class InternshipController extends Controller
{
    protected $repository;
    protected $service;

    public function __construct(InternshipRepositoryInterface $repository, InternshipService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Internship::with(['company.companyProfile'])->withCount('applications');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $query->where(function($q) use ($status) {
                if ($status === 'open') {
                    $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), ['open', 'active', 'published'])
                      ->orWhereNull('status');
                } else {
                    $q->where(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), $status);
                }
            });
        }

        $perPage = $request->input('per_page', 15);
        $internships = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $internships
        ]);
    }

    public function store(InternshipRequest $request)
    {
        $internship = $this->repository->createInternship($request->validated());
        return response()->json(['success' => true, 'data' => $internship], 201);
    }

    public function show($id)
    {
        $internship = $this->repository->getInternshipById($id);
        return response()->json(['success' => true, 'data' => $internship]);
    }

    public function update(InternshipRequest $request, $id)
    {
        $internship = $this->repository->updateInternship($id, $request->validated());
        return response()->json(['success' => true, 'data' => $internship]);
    }

    public function destroy($id)
    {
        $this->repository->deleteInternship($id);
        return response()->json(['success' => true, 'message' => 'Internship deleted']);
    }

    public function duplicate(Request $request, $id)
    {
        $newInternship = $this->service->duplicateInternship($id, $request->user()->id);
        return response()->json(['success' => true, 'data' => $newInternship]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:internships,id',
            'status' => 'required|in:open,closed,draft,archived'
        ]);

        $this->repository->bulkUpdateStatus($request->ids, $request->status);
        return response()->json(['success' => true, 'message' => 'Statuses updated']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:internships,id',
        ]);

        $this->repository->bulkDelete($request->ids);
        return response()->json(['success' => true, 'message' => 'Internships deleted']);
    }

    public function stats(): \Illuminate\Http\JsonResponse
    {
        $stats = [
            'total'       => \App\Models\Internship::count(),
            'open'        => \App\Models\Internship::where('status', 'open')->count(),
            'draft'       => \App\Models\Internship::where('status', 'draft')->count(),
            'closed'      => \App\Models\Internship::where('status', 'closed')->count(),
            'archived'    => \App\Models\Internship::where('status', 'archived')->count(),
            'applications' => \App\Models\InternshipApplication::count(),
            'pending'     => \App\Models\InternshipApplication::where('status', 'pending')->count(),
            'approved'    => \App\Models\InternshipApplication::where('status', 'approved')->count(),
            'rejected'    => \App\Models\InternshipApplication::where('status', 'rejected')->count(),
            'submissions' => \App\Models\InternshipSubmission::count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $internships = \App\Models\Internship::with('company')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $format = $request->get('format', 'csv');

        if ($format === 'csv') {
            $headers = ['ID', 'Title', 'Company', 'Status', 'Mode', 'Stipend', 'Openings', 'Start Date', 'End Date', 'Applications', 'Created At'];
            $rows    = $internships->map(fn($i) => [
                $i->id,
                $i->title,
                $i->company?->first_name . ' ' . $i->company?->last_name,
                $i->status,
                $i->mode,
                $i->stipend,
                $i->openings,
                $i->start_date?->format('Y-m-d'),
                $i->end_date?->format('Y-m-d'),
                $i->applications()->count(),
                $i->created_at->format('Y-m-d'),
            ]);

            $csv = implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
            }

            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="internships-export.csv"',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unsupported format'], 400);
    }

    public function sampleCsv()
    {
        $headers = [
            'title', 'company', 'department', 'mode', 'location', 
            'duration', 'duration_months', 'stipend', 'openings', 
            'skills_required', 'eligibility', 'description', 
            'responsibilities', 'learning_outcomes', 'application_deadline', 'status'
        ];

        $sampleRow1 = [
            'Full Stack Web Development Intern',
            'TechCorp Global',
            'Engineering',
            'Remote',
            'Bangalore, India',
            '6 Months',
            '6',
            '15000',
            '5',
            'React, Next.js, Node.js, TypeScript, PostgreSQL',
            'B.Tech / MCA / BCA students or recent graduates with passion for web dev',
            'Hands-on live production project internship working with modern web application architecture.',
            'Develop responsive frontend UI; Collaborate on REST API integrations; Participate in agile sprint reviews',
            'Production React development, REST API design, Git team workflow, CI/CD deployment',
            '2026-10-31',
            'open'
        ];

        $sampleRow2 = [
            'UI/UX Design Intern',
            'DesignHub Studio',
            'Design',
            'Hybrid',
            'Mumbai, India',
            '3 Months',
            '3',
            '12000',
            '3',
            'Figma, Design Systems, Wireframing, User Research, Adobe XD',
            'Design graduates or enthusiastic self-taught UI/UX learners with an active portfolio',
            'Work alongside senior product designers to create user-centric digital experiences.',
            'Conduct user interviews; Build wireframes and clickable prototypes; Design component tokens',
            'Mastery of Figma components, design tokens, client design handoff',
            '2026-10-31',
            'open'
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        fputcsv($csv, $sampleRow1);
        fputcsv($csv, $sampleRow2);

        rewind($csv);
        $csvData = stream_get_contents($csv);
        fclose($csv);

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="internships-sample-template.csv"');
    }

    /**
     * Import internships from uploaded CSV file
     * POST /api/admin/internships/import
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

        // Default Company / Admin user
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

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Skip empty lines
                if (count(array_filter($row)) === 0) continue;

                $title = $getVal($row, ['title', 'internshiptitle', 'role', 'position', 'name']);
                if (empty($title)) {
                    $errors[] = "Row {$rowNum}: Missing Internship Title.";
                    continue;
                }

                $companyName = $getVal($row, ['company', 'companyname', 'employer', 'organization'], 'BlueBoxx Partner');
                $department = $getVal($row, ['department', 'dept', 'category'], 'Engineering');
                $location = $getVal($row, ['location', 'city', 'internshiplocation'], 'India');
                $modeRaw = strtolower($getVal($row, ['mode', 'workplacetype', 'remotetype', 'workmode'], 'Remote'));
                $mode = 'Remote';
                if (in_array($modeRaw, ['hybrid', 'onsite', 'remote'])) {
                    $mode = ucfirst($modeRaw);
                }

                $durationMonths = (int)$getVal($row, ['durationmonths', 'months'], 3);
                $duration = $getVal($row, ['duration'], ($durationMonths > 0 ? "{$durationMonths} Months" : '3 Months'));
                $stipend = $getVal($row, ['stipend', 'salary', 'allowance'], null);
                $openings = (int)$getVal($row, ['openings', 'vacancies', 'positions'], 1);
                $eligibility = $getVal($row, ['eligibility', 'qualification', 'qualifications'], 'Open to all students and recent graduates.');
                $description = $getVal($row, ['description', 'internshipdescription', 'desc'], "Gain valuable industry experience in {$title}.");
                $responsibilities = $getVal($row, ['responsibilities', 'rolesresponsibilities', 'duties'], 'Participate in project deliverables and team meetings.');
                $learningOutcomes = $getVal($row, ['learningoutcomes', 'outcomes', 'learnings'], 'Practical industry exposure and project execution skills.');
                $deadlineRaw = $getVal($row, ['applicationdeadline', 'deadline'], null);
                $deadline = $deadlineRaw ? date('Y-m-d', strtotime($deadlineRaw)) : now()->addDays(45)->format('Y-m-d');

                $statusRaw = strtolower($getVal($row, ['status'], 'open'));
                $status = in_array($statusRaw, ['open', 'draft', 'closed', 'archived']) ? $statusRaw : 'open';

                // Parse list of skills
                $skillsRaw = $getVal($row, ['skillsrequired', 'skills', 'keyskills', 'requiredskills'], '');
                $skills = [];
                if ($skillsRaw) {
                    $items = preg_split('/[,\n\r;|]+/', $skillsRaw);
                    $skills = array_values(array_filter(array_map('trim', $items)));
                }

                \App\Models\Internship::create([
                    'company_id'           => $defaultCompanyId,
                    'title'                => $title,
                    'department'           => $department,
                    'location'             => $location,
                    'mode'                 => $mode,
                    'duration_months'      => max(1, $durationMonths),
                    'duration'             => $duration,
                    'stipend'              => is_numeric($stipend) ? (float)$stipend : null,
                    'skills_required'      => $skills,
                    'eligibility'          => $eligibility,
                    'description'          => $description,
                    'responsibilities'     => $responsibilities,
                    'learning_outcomes'    => $learningOutcomes,
                    'openings'             => max(1, $openings),
                    'application_deadline' => $deadline,
                    'status'               => $status,
                    'featured'             => $imported < 5,
                ]);

                $imported++;
            }

            \Illuminate\Support\Facades\DB::commit();
            fclose($handle);

            return response()->json([
                'success'        => true,
                'message'        => "Successfully imported {$imported} internships.",
                'imported_count' => $imported,
                'errors'         => $errors,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            if (is_resource($handle)) fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'CSV Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}


