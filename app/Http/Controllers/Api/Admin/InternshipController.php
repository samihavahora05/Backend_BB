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
                                   ->orWhere('last_name', 'like', "%{$search}%");
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

    public function export(Request $request)
    {
        $internships = \App\Models\Internship::with('company')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $format = $request->get('format', 'csv');

        if ($format === 'csv' || $format === 'excel' || $format === 'xlsx') {
            $headers = ['ID', 'Title', 'Company', 'Company Logo URL', 'Department', 'Status', 'Mode', 'Location', 'Duration', 'Duration Months', 'Stipend', 'Openings', 'Skills Required', 'Eligibility', 'Description', 'Responsibilities', 'Learning Outcomes', 'Application Deadline', 'Created At'];
            $rows    = $internships->map(function($i) {
                $companyLogo = $i->company?->companyProfile?->logo ? \App\Support\StorageHelper::url($i->company->companyProfile->logo) : ($i->company?->avatar_url ? \App\Support\StorageHelper::url($i->company->avatar_url) : '');
                $skillsStr = is_array($i->skills_required) ? implode(', ', $i->skills_required) : ($i->skills_required ?? '');
                
                return [
                    $i->id,
                    $i->title,
                    trim(($i->company?->first_name ?? '') . ' ' . ($i->company?->last_name ?? '')) ?: ($i->company?->name ?? 'BlueBoxx Partner'),
                    $companyLogo,
                    $i->department ?? 'Engineering',
                    $i->status ?? 'open',
                    $i->mode ?? 'Remote',
                    $i->location ?? 'India',
                    $i->duration ?? '3 Months',
                    $i->duration_months ?? 3,
                    $i->stipend ?? 'Unpaid',
                    $i->openings ?? 1,
                    $skillsStr,
                    $i->eligibility ?? '',
                    $i->description ?? '',
                    $i->responsibilities ?? '',
                    $i->learning_outcomes ?? '',
                    $i->application_deadline ? $i->application_deadline->format('Y-m-d') : '',
                    $i->created_at ? $i->created_at->format('Y-m-d H:i:s') : '',
                ];
            });

            $csv = implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)($v ?? '')) . '"', $row)) . "\n";
            }

            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="internships-export.csv"',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unsupported format'], 400);
    }

    /**
     * Download sample Excel or CSV template for Internship Import
     * GET /api/admin/internships/sample-template
     */
    public function sampleTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $headers = [
            'Internship Title',
            'Company Name',
            'Company Logo URL',
            'Department / Domain',
            'Work Mode (Remote/Hybrid/Onsite)',
            'Location (City, Country)',
            'Duration (e.g. 3 Months)',
            'Duration Months (Number)',
            'Stipend (Monthly INR)',
            'Openings',
            'Required Skills (Comma separated)',
            'Eligibility Criteria',
            'Internship Description',
            'Roles & Responsibilities',
            'Learning Outcomes',
            'Application Deadline (YYYY-MM-DD)',
            'Status (open/draft)'
        ];

        $sampleRows = [
            [
                'Full Stack Web Development Intern',
                'TechCorp Global Solutions',
                'https://images.unsplash.com/photo-1572021335469-31706a17aaef?auto=format&fit=crop&w=300&q=80',
                'Engineering',
                'Remote',
                'Bangalore, India',
                '6 Months',
                '6',
                '15000',
                '5',
                'React.js, Next.js, Node.js, TypeScript, PostgreSQL, REST APIs, Git',
                'B.Tech / MCA / BCA students or recent graduates with solid foundations in JavaScript.',
                'Join our core platform engineering team to build scalable full-stack web applications and microservices.',
                'Design and implement responsive web UI; Build high-performance REST APIs; Write unit and integration tests; Participate in agile sprint planning.',
                'Production-grade full-stack architecture, database optimization, CI/CD pipeline automation, and collaborative Git workflows.',
                now()->addDays(45)->format('Y-m-d'),
                'open'
            ],
            [
                'UI/UX Design Intern',
                'DesignHub Digital Studio',
                'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=300&q=80',
                'Design',
                'Hybrid',
                'Mumbai, India',
                '3 Months',
                '3',
                '12000',
                '3',
                'Figma, UI Design, Wireframing, Prototyping, Design Systems, User Research',
                'Design graduates or enthusiastic self-taught UI/UX designers with a strong portfolio.',
                'Collaborate closely with product managers and engineers to create intuitive, accessible user interfaces.',
                'Conduct user research and usability testing; Create wireframes, user journeys, and high-fidelity mockups; Build and maintain design system tokens.',
                'Mastery of Figma component systems, interactive prototyping, user-centered design principles, and developer handoff workflows.',
                now()->addDays(30)->format('Y-m-d'),
                'open'
            ],
            [
                'Data Analyst & AI Intern',
                'FinMetrics Analytics',
                'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=300&q=80',
                'Data Science',
                'Remote',
                'Hyderabad, India',
                '4 Months',
                '4',
                '18000',
                '4',
                'Python, SQL, Pandas, NumPy, PowerBI, Machine Learning, Data Visualization',
                'Students or graduates in Computer Science, Statistics, Mathematics, or Data Science.',
                'Work on predictive analytics and AI-powered data pipelines for enterprise financial intelligence.',
                'Extract and clean complex datasets; Build automated analytical dashboards in PowerBI; Train baseline machine learning models; Present data-driven insights to leadership.',
                'End-to-end data pipeline construction, statistical analysis, dashboarding best practices, and enterprise ML integration.',
                now()->addDays(60)->format('Y-m-d'),
                'open'
            ]
        ];

        if ($format === 'csv') {
            $csv = fopen('php://temp', 'r+');
            fputcsv($csv, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($csv, $row);
            }
            rewind($csv);
            $csvData = stream_get_contents($csv);
            fclose($csv);

            return response($csvData, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="internships-sample-template.csv"',
            ]);
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Internship Template');

            foreach ($headers as $index => $header) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
                $sheet->setCellValue($cellCoordinate, $header);
            }

            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            $headerRange = "A1:{$lastCol}1";
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B2A6B']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true]
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);

            foreach ($sampleRows as $rowIndex => $rowData) {
                $rowNum = $rowIndex + 2;
                foreach ($rowData as $colIndex => $value) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $rowNum;
                    $sheet->setCellValue($cellCoordinate, $value);
                }
                $sheet->getRowDimension($rowNum)->setRowHeight(22);
            }

            for ($i = 1; $i <= count($headers); $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_template_');
            $writer->save($tempPath);

            return response()->download($tempPath, 'internships-sample-template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            return $this->sampleCsv();
        }
    }

    public function sampleCsv()
    {
        return $this->sampleTemplate(new Request(['format' => 'csv']));
    }

    private function parseUploadedFileMatrix($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            return $sheet->toArray(null, true, true, false);
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    private function normalizeRowData(array $rawRow, array $headerMap, int $rowNumber)
    {
        $getVal = function ($keys, $default = null) use ($rawRow, $headerMap) {
            foreach ((array)$keys as $k) {
                $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $k)));
                if (isset($headerMap[$clean]) && isset($rawRow[$headerMap[$clean]])) {
                    $val = trim((string)$rawRow[$headerMap[$clean]]);
                    if ($val !== '') return $val;
                }
            }
            return $default;
        };

        $title = $getVal(['title', 'internshiptitle', 'role', 'position', 'name', 'jobtitle']);
        $companyName = $getVal(['company', 'companyname', 'employer', 'organization', 'hiringcompany'], 'BlueBoxx Partner');
        $companyLogo = $getVal(['companylogo', 'logo', 'logourl', 'image', 'thumbnail', 'banner']);
        $department = $getVal(['department', 'dept', 'category', 'domain', 'field', 'stream'], 'Engineering');
        $location = $getVal(['location', 'city', 'internshiplocation', 'office', 'place'], 'Remote');
        
        $modeRaw = strtolower($getVal(['mode', 'workmode', 'workplacetype', 'remotetype', 'worktype'], 'Remote'));
        $mode = 'Remote';
        if (str_contains($modeRaw, 'hybrid')) {
            $mode = 'Hybrid';
        } elseif (str_contains($modeRaw, 'onsite') || str_contains($modeRaw, 'on-site') || str_contains($modeRaw, 'office')) {
            $mode = 'Onsite';
        }

        $durationMonthsRaw = $getVal(['durationmonths', 'months', 'durationinmonths']);
        $durationRaw = $getVal(['duration', 'period', 'tenure']);
        
        $durationMonths = 3;
        if (is_numeric($durationMonthsRaw) && (int)$durationMonthsRaw > 0) {
            $durationMonths = (int)$durationMonthsRaw;
        } elseif ($durationRaw && preg_match('/(\d+)/', $durationRaw, $matches)) {
            $durationMonths = (int)$matches[1];
        }

        $duration = $durationRaw ?: "{$durationMonths} Months";

        $stipendRaw = $getVal(['stipend', 'salary', 'allowance', 'compensation', 'pay', 'ctc']);
        $stipend = null;
        if ($stipendRaw !== null) {
            $cleanStipend = preg_replace('/[^0-9.]/', '', (string)$stipendRaw);
            if (is_numeric($cleanStipend)) {
                $stipend = (float)$cleanStipend;
            }
        }

        $openingsRaw = $getVal(['openings', 'vacancies', 'positions', 'seats', 'capacity'], '1');
        $openings = max(1, (int)preg_replace('/[^0-9]/', '', (string)$openingsRaw));

        $skillsRaw = $getVal(['skillsrequired', 'skills', 'keyskills', 'requiredskills', 'techstack', 'technologies'], '');
        $skills = [];
        if ($skillsRaw) {
            $parts = preg_split('/[,\n\r;|]+/', (string)$skillsRaw);
            $skills = array_values(array_filter(array_map('trim', $parts)));
        }

        $eligibility = $getVal(['eligibility', 'qualification', 'qualifications', 'criteria', 'requirements'], 'Open to all eligible students and recent graduates.');
        $description = $getVal(['description', 'internshipdescription', 'aboutinternship', 'overview', 'details'], $title ? "Hands-on professional industry experience in {$title}." : '');
        $responsibilities = $getVal(['responsibilities', 'rolesresponsibilities', 'duties', 'whatyouwilldo'], 'Collaborate with cross-functional project teams and deliver sprint goals.');
        $learningOutcomes = $getVal(['learningoutcomes', 'outcomes', 'learnings', 'whatyouwilllearn'], 'Industry domain experience, software engineering best practices, and team collaboration.');

        $deadlineRaw = $getVal(['applicationdeadline', 'deadline', 'lastdate', 'applyby', 'expiry']);
        $deadline = null;
        if ($deadlineRaw) {
            $time = strtotime($deadlineRaw);
            if ($time !== false) {
                $deadline = date('Y-m-d', $time);
            }
        }
        if (!$deadline) {
            $deadline = now()->addDays(45)->format('Y-m-d');
        }

        $statusRaw = strtolower($getVal(['status'], 'open'));
        $status = in_array($statusRaw, ['open', 'draft', 'closed', 'archived']) ? $statusRaw : 'open';

        return [
            'row_number'           => $rowNumber,
            'is_valid'             => true,
            'is_duplicate'         => false,
            'errors'               => [],
            'data'                 => [
                'title'                => $title,
                'company_name'         => $companyName,
                'company_logo'         => $companyLogo,
                'thumbnail'            => $companyLogo,
                'department'           => $department,
                'location'             => $location,
                'mode'                 => $mode,
                'duration_months'      => $durationMonths,
                'duration'             => $duration,
                'stipend'              => $stipend,
                'openings'             => $openings,
                'skills_required'      => $skills,
                'eligibility'          => $eligibility,
                'description'          => $description,
                'responsibilities'     => $responsibilities,
                'learning_outcomes'    => $learningOutcomes,
                'application_deadline' => $deadline,
                'status'               => $status,
            ]
        ];
    }

    /**
     * Preview and Validate Excel / CSV file without saving
     * POST /api/admin/internships/import/preview
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        
        try {
            $rawMatrix = $this->parseUploadedFileMatrix($file);
            
            if (empty($rawMatrix) || count($rawMatrix) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file appears to be empty or missing data rows.'
                ], 422);
            }

            $headerRow = array_shift($rawMatrix);
            if (isset($headerRow[0])) {
                $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headerRow[0]);
            }

            $headerMap = [];
            foreach ($headerRow as $colIdx => $h) {
                $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$h)));
                if ($clean !== '') {
                    $headerMap[$clean] = $colIdx;
                }
            }

            $existingTitles = \App\Models\Internship::pluck('title')->map(fn($t) => strtolower(trim($t)))->toArray();
            $existingSet = array_flip($existingTitles);

            $processedRows = [];
            $validCount = 0;
            $invalidCount = 0;
            $duplicateCount = 0;

            foreach ($rawMatrix as $idx => $rawRow) {
                if (empty(array_filter($rawRow, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                $rowNum = $idx + 2;
                $rowItem = $this->normalizeRowData($rawRow, $headerMap, $rowNum);
                $normalized = $rowItem['data'];
                
                $rowErrors = [];
                $isValid = true;
                $isDuplicate = false;

                if (empty($normalized['title'])) {
                    $rowErrors[] = 'Internship Title is required.';
                    $isValid = false;
                } elseif (strlen($normalized['title']) < 3) {
                    $rowErrors[] = 'Internship Title must be at least 3 characters.';
                    $isValid = false;
                }

                if ($normalized['openings'] < 1) {
                    $rowErrors[] = 'Openings must be a positive number greater than 0.';
                    $isValid = false;
                }

                if ($normalized['duration_months'] < 1) {
                    $rowErrors[] = 'Duration must be at least 1 month.';
                    $isValid = false;
                }

                if (!empty($normalized['title'])) {
                    $cleanTitle = strtolower(trim($normalized['title']));
                    if (isset($existingSet[$cleanTitle])) {
                        $isDuplicate = true;
                    }
                }

                $rowItem['is_valid'] = $isValid;
                $rowItem['is_duplicate'] = $isDuplicate;
                $rowItem['errors'] = $rowErrors;

                if ($isValid && !$isDuplicate) {
                    $validCount++;
                } elseif ($isDuplicate) {
                    $duplicateCount++;
                } else {
                    $invalidCount++;
                }

                $processedRows[] = $rowItem;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_detected'   => count($processedRows),
                    'valid_count'      => $validCount,
                    'invalid_count'    => $invalidCount,
                    'duplicate_count'  => $duplicateCount,
                    'rows'             => $processedRows,
                    'headers_found'    => array_keys($headerMap)
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Confirm and Execute Internship Import into Database
     * POST /api/admin/internships/import/confirm
     */
    public function confirmImport(Request $request)
    {
        $request->validate([
            'rows'             => 'required|array|min:1',
            'initial_status'   => 'nullable|in:open,draft',
            'skip_duplicates'  => 'nullable|boolean',
            'skip_invalid'     => 'nullable|boolean',
        ]);

        $rows = $request->input('rows');
        $initialStatus = $request->input('initial_status', 'open');
        $skipDuplicates = filter_var($request->input('skip_duplicates', true), FILTER_VALIDATE_BOOLEAN);
        $skipInvalid = filter_var($request->input('skip_invalid', true), FILTER_VALIDATE_BOOLEAN);

        $defaultCompany = \App\Models\User::role('company')->first() 
            ?? \App\Models\User::role('admin')->first() 
            ?? \App\Models\User::role('super_admin')->first()
            ?? auth()->user();

        $defaultCompanyId = $defaultCompany ? $defaultCompany->id : 1;

        $imported = 0;
        $skipped = 0;
        $errors = [];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($rows as $item) {
                // Support both flat row and nested row
                $data = isset($item['data']) ? $item['data'] : $item;
                $isValid = $item['is_valid'] ?? true;
                $isDuplicate = $item['is_duplicate'] ?? false;

                if (!$isValid && $skipInvalid) {
                    $skipped++;
                    continue;
                }

                if ($isDuplicate && $skipDuplicates) {
                    $skipped++;
                    continue;
                }

                if (empty($data['title'])) {
                    $skipped++;
                    continue;
                }

                $companyId = $defaultCompanyId;
                if (!empty($data['company_name'])) {
                    $matchedUser = \App\Models\User::where('first_name', 'like', "%{$data['company_name']}%")
                        ->orWhere('last_name', 'like', "%{$data['company_name']}%")
                        ->first();
                    if ($matchedUser) {
                        $companyId = $matchedUser->id;
                    }
                }

                $statusToApply = $initialStatus;
                if (!empty($data['status']) && in_array($data['status'], ['open', 'draft', 'closed', 'archived'])) {
                    $statusToApply = $initialStatus === 'draft' ? 'draft' : $data['status'];
                }

                \App\Models\Internship::create([
                    'company_id'           => $companyId,
                    'title'                => trim($data['title']),
                    'department'           => $data['department'] ?? 'Engineering',
                    'location'             => $data['location'] ?? 'Remote',
                    'mode'                 => in_array($data['mode'] ?? '', ['Remote', 'Hybrid', 'Onsite']) ? $data['mode'] : 'Remote',
                    'duration_months'      => max(1, (int)($data['duration_months'] ?? 3)),
                    'duration'             => $data['duration'] ?? '3 Months',
                    'stipend'              => is_numeric($data['stipend'] ?? null) ? (float)$data['stipend'] : null,
                    'skills_required'      => is_array($data['skills_required'] ?? null) ? $data['skills_required'] : [],
                    'eligibility'          => $data['eligibility'] ?? 'Open to all eligible candidates.',
                    'description'          => $data['description'] ?? "Internship opportunity in {$data['title']}.",
                    'responsibilities'     => $data['responsibilities'] ?? 'Perform assigned project tasks and sprint deliverables.',
                    'learning_outcomes'    => $data['learning_outcomes'] ?? 'Practical project experience and engineering skills.',
                    'openings'             => max(1, (int)($data['openings'] ?? 1)),
                    'application_deadline' => !empty($data['application_deadline']) ? $data['application_deadline'] : now()->addDays(45)->format('Y-m-d'),
                    'status'               => $statusToApply,
                    'featured'             => $imported < 3,
                ]);

                $imported++;
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => "Successfully imported {$imported} internships into the database.",
                'data'           => [
                    'imported_count'           => $imported,
                    'skipped_duplicates_count' => $skipped,
                    'failed_count'             => count($errors),
                ],
                'errors'         => $errors,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importCsv(Request $request)
    {
        $file = $request->file('file') ?? $request->file('csv_file');

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid file.'], 422);
        }

        $previewRes = $this->previewImport($request);
        $previewData = json_decode($previewRes->getContent(), true);

        if (!$previewData || empty($previewData['success'])) {
            return $previewRes;
        }

        $confirmReq = new Request([
            'rows'            => $previewData['data']['rows'] ?? [],
            'initial_status'  => 'open',
            'skip_duplicates' => true,
            'skip_invalid'    => true,
        ]);

        return $this->confirmImport($confirmReq);
    }
}