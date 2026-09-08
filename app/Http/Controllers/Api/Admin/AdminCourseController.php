<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminCourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index(Request $request)
    {
        $courses = $this->courseService->getCourses($request->all());
        
        // Match the frontend SWR format expectations
        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total()
            ]
        ]);
    }

    protected function sanitizeRequest(Request $request): void
    {
        $inputs = $request->all();

        // Convert empty string IDs to null
        foreach (['level_id', 'expert_id', 'category_id'] as $field) {
            if (isset($inputs[$field]) && (trim((string)$inputs[$field]) === '' || $inputs[$field] === 'null' || $inputs[$field] === 'undefined')) {
                $inputs[$field] = null;
            }
        }

        // Auto-resolve expert_id if an ExpertProfile ID was passed instead of User ID
        if (!empty($inputs['expert_id'])) {
            $userExists = \App\Models\User::where('id', $inputs['expert_id'])->exists();
            if (!$userExists) {
                $expertProfile = \App\Models\ExpertProfile::find($inputs['expert_id']);
                if ($expertProfile && $expertProfile->user_id) {
                    $inputs['expert_id'] = $expertProfile->user_id;
                } else {
                    $inputs['expert_id'] = auth()->id() ?? \App\Models\User::first()?->id ?? 1;
                }
            }
        }

        // Convert empty numeric fields to null
        foreach (['price', 'discount_price', 'duration_hours'] as $field) {
            if (isset($inputs[$field]) && (trim((string)$inputs[$field]) === '' || $inputs[$field] === 'null')) {
                $inputs[$field] = null;
            }
        }

        // Unset non-file thumbnail string if passed so 'nullable|image' validation rule passes
        if (isset($inputs['thumbnail']) && !($inputs['thumbnail'] instanceof \Illuminate\Http\UploadedFile)) {
            unset($inputs['thumbnail']);
        }

        // Sanitize landing page URL if empty
        if (isset($inputs['landing_page_url']) && trim((string)$inputs['landing_page_url']) === '') {
            $inputs['landing_page_url'] = null;
        }

        $request->replace($inputs);
    }

    public function store(Request $request)
    {
        $this->sanitizeRequest($request);

        $data = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'level_id' => 'nullable|exists:course_levels,id',
            'expert_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:5120',
            'preview_video_url' => 'nullable|string|max:255',
            'demo_pdf_url' => 'nullable|string|max:255',
            'landing_page_url' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'course_type' => ['nullable', Rule::in(['Free', 'Paid'])],
            'language' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:255',
            'duration_hours' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])],
            'is_featured' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        if (empty($data['expert_id'])) {
            $data['expert_id'] = auth()->id() ?? 1;
        }
        
        $data['price'] = $data['price'] ?? 0;
        $data['discount_price'] = $data['discount_price'] ?? 0;

        if (!array_key_exists('is_published', $data)) {
            $data['is_published'] = ($data['status'] ?? null) === 'Published';
        }

        $course = $this->courseService->createCourse($data);
        return response()->json(['success' => true, 'message' => 'Course created successfully', 'data' => $course], 201);
    }

    public function show($id)
    {
        $course = Course::with(['category', 'level', 'expert'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $course]);
    }

    public function update(Request $request, $id)
    {
        $this->sanitizeRequest($request);

        $course = Course::findOrFail($id);

        $data = $request->validate([
            'category_id' => 'nullable|exists:course_categories,id',
            'level_id' => 'nullable|exists:course_levels,id',
            'expert_id' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:5120',
            'preview_video_url' => 'nullable|string|max:255',
            'demo_pdf_url' => 'nullable|string|max:255',
            'landing_page_url' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'course_type' => ['nullable', Rule::in(['Free', 'Paid'])],
            'language' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:255',
            'duration_hours' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])],
            'is_featured' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        if (array_key_exists('status', $data) && !array_key_exists('is_published', $data)) {
            $data['is_published'] = $data['status'] === 'Published';
        }

        $course = $this->courseService->updateCourse($course, $data);
        return response()->json(['success' => true, 'message' => 'Course updated successfully', 'data' => $course]);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $this->courseService->deleteCourse($course);
        return response()->json(['status' => 'success', 'message' => 'Course deleted successfully']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $this->courseService->bulkDelete($request->ids);
        return response()->json(['status' => 'success', 'message' => 'Courses deleted successfully']);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])]
        ]);
        $this->courseService->bulkStatus($request->ids, $request->status);
        return response()->json(['status' => 'success', 'message' => 'Status updated successfully']);
    }

    public function duplicate($id)
    {
        $course = $this->courseService->duplicateCourse($id);
        return response()->json(['status' => 'success', 'message' => 'Course duplicated successfully', 'data' => $course]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => ['required', Rule::in(['Draft', 'Published', 'Private', 'Pending Approval', 'Rejected'])]]);
        $course = Course::findOrFail($id);
        $this->courseService->updateStatus($course, $request->status);
        return response()->json(['status' => 'success', 'message' => 'Status updated']);
    }

    public function toggleArchive($id)
    {
        $course = Course::findOrFail($id);
        $this->courseService->toggleArchive($course);
        return response()->json(['status' => 'success', 'message' => $course->is_archived ? 'Course archived' : 'Course unarchived']);
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $export = new \App\Exports\CoursesExport();
        
        if ($format === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download($export, 'courses.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        
        if ($format === 'pdf') {
            $courses = Course::with(['category', 'expert'])->latest()->get();
            $html = '<html><head><title>Courses Export</title><style>body { font-family: sans-serif; } table {width:100%; border-collapse: collapse; margin-top: 20px;} th, td {border:1px solid #ddd; padding:8px; text-align:left; font-size: 12px; vertical-align: middle;} th {background:#1B2A6B; color:white;} img.thumb {width: 48px; height: 48px; object-fit: cover; border-radius: 8px;} @media print { button { display: none; } }</style></head><body onload="window.print()">';
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center;"><h2>Courses Export</h2><button onclick="window.print()" style="padding: 8px 16px; background: #1B2A6B; color: white; border: none; border-radius: 4px; cursor: pointer;">Print to PDF</button></div>';
            $html .= '<table><tr><th>ID</th><th>Thumbnail</th><th>Course Title</th><th>Category</th><th>Instructor</th><th>Type</th><th>Price</th><th>Status</th></tr>';
            foreach($courses as $c) {
                $imgUrl = $c->thumbnail ? \App\Support\StorageHelper::url($c->thumbnail) : '';
                $imgTag = $imgUrl ? "<img src='{$imgUrl}' class='thumb' alt='thumbnail' />" : "<span style='color:#999;'>No Image</span>";
                $instructor = trim(($c->expert->first_name ?? '').' '.($c->expert->last_name ?? '')) ?: ($c->expert->name ?? 'N/A');
                $html .= "<tr><td>{$c->id}</td><td>{$imgTag}</td><td><strong>{$c->title}</strong></td><td>".($c->category->name ?? 'N/A')."</td><td>{$instructor}</td><td>{$c->course_type}</td><td>₹{$c->price}</td><td>{$c->status}</td></tr>";
            }
            $html .= '</table></body></html>';
            return response($html)->header('Content-Type', 'text/html');
        }

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'courses.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Download sample Excel or CSV template for Course Import
     * GET /api/admin/courses/sample-template
     */
    public function sampleTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $headers = [
            'Course Title',
            'Category Name',
            'Level (Beginner/Intermediate/Advanced)',
            'Instructor Name / Email',
            'Course Type (Free/Paid)',
            'Price (INR)',
            'Discount Price (INR)',
            'Duration (e.g. 24 Hours, 6 Weeks)',
            'Language',
            'Thumbnail Image URL',
            'Short Description',
            'Full Description',
            'Status (Published/Draft)',
            'Featured (Yes/No)'
        ];

        $sampleRows = [
            [
                'Full Stack Web Development with React & Next.js',
                'Web Development',
                'Beginner to Intermediate',
                'Dr. Sarah Jenkins',
                'Paid',
                '2499',
                '1499',
                '36 Hours',
                'English',
                'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80',
                'Master modern full stack web development building production applications with React, Next.js, Node and SQL.',
                'Comprehensive step-by-step masterclass taking you from JavaScript fundamentals to building full-stack cloud-native applications with authentication, databases, and CI/CD.',
                'Published',
                'Yes'
            ],
            [
                'UI/UX Design Masterclass: Figma & Design Systems',
                'Design',
                'All Levels',
                'Alex Rivera',
                'Paid',
                '1999',
                '999',
                '24 Hours',
                'English',
                'https://images.unsplash.com/photo-1581291518655-9523c932edcf?auto=format&fit=crop&w=600&q=80',
                'Learn UI/UX design from scratch: wireframing, interactive prototyping, and building robust design systems in Figma.',
                'Create user-centered mobile apps and web platforms with industry-standard design thinking, usability research, and developer handoff workflows.',
                'Published',
                'Yes'
            ],
            [
                'Introduction to Python for Data Science & AI',
                'Data Science',
                'Beginner',
                'Prof. Michael Chang',
                'Free',
                '0',
                '0',
                '18 Hours',
                'English',
                'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=600&q=80',
                'Free foundational course on Python programming, NumPy, Pandas, and data visualization basics.',
                'Hands-on beginner tutorial covering variables, data structures, functions, data manipulation with Pandas, and plotting charts.',
                'Published',
                'No'
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
                'Content-Disposition' => 'attachment; filename="courses-sample-template.csv"',
            ]);
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Courses Template');

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
            $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_course_template_');
            $writer->save($tempPath);

            return response()->download($tempPath, 'courses-sample-template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            $csv = fopen('php://temp', 'r+');
            fputcsv($csv, $headers);
            foreach ($sampleRows as $row) fputcsv($csv, $row);
            rewind($csv);
            $csvData = stream_get_contents($csv);
            fclose($csv);
            return response($csvData, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="courses-sample-template.csv"']);
        }
    }

    /**
     * Preview and Validate Excel / CSV file for Courses
     * POST /api/admin/courses/import/preview
     */
    public function previewImport(Request $request)
    {
        $request->validate(['file' => 'required|file|max:20480']);
        $file = $request->file('file');

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $filePath = $file->getRealPath();

            if (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $rawMatrix = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } else {
                $rawMatrix = [];
                if (($handle = fopen($filePath, 'r')) !== false) {
                    while (($data = fgetcsv($handle)) !== false) {
                        $rawMatrix[] = $data;
                    }
                    fclose($handle);
                }
            }

            if (empty($rawMatrix) || count($rawMatrix) < 2) {
                return response()->json(['success' => false, 'message' => 'The uploaded file appears to be empty.'], 422);
            }

            $headerRow = array_shift($rawMatrix);
            if (isset($headerRow[0])) $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headerRow[0]);

            $headerMap = [];
            foreach ($headerRow as $colIdx => $h) {
                $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$h)));
                if ($clean !== '') $headerMap[$clean] = $colIdx;
            }

            $existingTitles = Course::pluck('title')->map(fn($t) => strtolower(trim($t)))->toArray();
            $existingSet = array_flip($existingTitles);

            $processedRows = [];
            $validCount = 0;
            $invalidCount = 0;
            $duplicateCount = 0;

            $getVal = function ($row, $keys, $default = null) use ($headerMap) {
                foreach ((array)$keys as $k) {
                    $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $k)));
                    if (isset($headerMap[$clean]) && isset($row[$headerMap[$clean]])) {
                        $val = trim((string)$row[$headerMap[$clean]]);
                        if ($val !== '') return $val;
                    }
                }
                return $default;
            };

            foreach ($rawMatrix as $idx => $rawRow) {
                if (empty(array_filter($rawRow, fn($v) => trim((string)$v) !== ''))) continue;

                $rowNum = $idx + 2;
                $title = $getVal($rawRow, ['title', 'coursetitle', 'name', 'coursename']);
                $categoryName = $getVal($rawRow, ['category', 'categoryname', 'domain', 'field', 'department'], 'Development');
                $levelTitle = $getVal($rawRow, ['level', 'leveltitle', 'experiencelevel', 'skilllevel'], 'Beginner');
                $instructorName = $getVal($rawRow, ['instructor', 'instructorname', 'expert', 'expertname', 'faculty', 'author'], 'Lead Faculty');
                $courseType = strtolower($getVal($rawRow, ['type', 'coursetype', 'pricingtype'], 'Paid')) === 'free' ? 'Free' : 'Paid';
                $priceRaw = $getVal($rawRow, ['price', 'cost', 'fee', 'regularprice'], 0);
                $price = is_numeric(preg_replace('/[^0-9.]/', '', (string)$priceRaw)) ? (float)preg_replace('/[^0-9.]/', '', (string)$priceRaw) : 0;
                $discountPriceRaw = $getVal($rawRow, ['discountprice', 'saleprice', 'offerprice'], null);
                $discountPrice = ($discountPriceRaw !== null && is_numeric(preg_replace('/[^0-9.]/', '', (string)$discountPriceRaw))) ? (float)preg_replace('/[^0-9.]/', '', (string)$discountPriceRaw) : null;
                $duration = $getVal($rawRow, ['duration', 'totalduration', 'hours', 'totalhours'], '24 Hours');
                $language = $getVal($rawRow, ['language', 'lang', 'medium'], 'English');
                $thumbnail = $getVal($rawRow, ['thumbnail', 'thumbnailurl', 'image', 'imageurl', 'cover', 'coverimage', 'banner'], null);
                $shortDesc = $getVal($rawRow, ['shortdescription', 'shortdesc', 'summary'], $title ? "Comprehensive masterclass in {$title}." : '');
                $description = $getVal($rawRow, ['description', 'fulldescription', 'about', 'details'], $shortDesc);
                $statusRaw = ucfirst(strtolower($getVal($rawRow, ['status', 'publishingstatus'], 'Published')));
                $status = in_array($statusRaw, ['Published', 'Draft', 'Private', 'Pending Approval', 'Rejected']) ? $statusRaw : 'Published';
                $featuredRaw = strtolower($getVal($rawRow, ['featured', 'isfeatured'], 'no'));
                $isFeatured = in_array($featuredRaw, ['yes', 'true', '1']);

                $rowErrors = [];
                $rowStatus = 'valid';
                $isDuplicate = false;

                if (empty($title)) {
                    $rowErrors[] = 'Course Title is required.';
                } elseif (strlen($title) < 3) {
                    $rowErrors[] = 'Course Title must be at least 3 characters.';
                }

                if (!empty($title)) {
                    $cleanTitle = strtolower(trim($title));
                    if (isset($existingSet[$cleanTitle])) {
                        $isDuplicate = true;
                        if (empty($rowErrors)) {
                            $rowStatus = 'duplicate';
                            $rowErrors[] = 'A course with this title already exists in the database.';
                        }
                    }
                }

                if (!empty($rowErrors) && $rowStatus !== 'duplicate') {
                    $rowStatus = 'invalid';
                }

                if ($rowStatus === 'valid') $validCount++;
                elseif ($rowStatus === 'duplicate') $duplicateCount++;
                else $invalidCount++;

                $processedRows[] = [
                    'row_number'        => $rowNum,
                    'row_status'        => $rowStatus,
                    'is_duplicate'      => $isDuplicate,
                    'errors'            => $rowErrors,
                    'title'             => $title,
                    'category_name'     => $categoryName,
                    'level_title'       => $levelTitle,
                    'instructor_name'   => $instructorName,
                    'course_type'       => $courseType,
                    'price'             => $price,
                    'discount_price'    => $discountPrice,
                    'duration'          => $duration,
                    'language'          => $language,
                    'thumbnail'         => $thumbnail,
                    'short_description' => $shortDesc,
                    'description'       => $description,
                    'status'            => $status,
                    'is_featured'       => $isFeatured,
                ];
            }

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_detected'  => count($processedRows),
                    'valid_count'     => $validCount,
                    'invalid_count'   => $invalidCount,
                    'duplicate_count' => $duplicateCount,
                ],
                'rows' => $processedRows
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to parse file: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Confirm and Execute Course Import into Database
     * POST /api/admin/courses/import/confirm
     */
    public function confirmImport(Request $request)
    {
        $request->validate([
            'rows'            => 'required|array|min:1',
            'initial_status'  => 'nullable|in:Published,Draft',
            'skip_duplicates' => 'nullable|boolean',
            'skip_invalid'    => 'nullable|boolean',
        ]);

        $rows = $request->input('rows');
        $initialStatus = $request->input('initial_status', 'Published');
        $skipDuplicates = filter_var($request->input('skip_duplicates', true), FILTER_VALIDATE_BOOLEAN);
        $skipInvalid = filter_var($request->input('skip_invalid', true), FILTER_VALIDATE_BOOLEAN);

        $defaultCategory = \App\Models\CourseCategory::first() ?? \App\Models\CourseCategory::create(['name' => 'General', 'slug' => 'general']);
        $defaultCategoryId = $defaultCategory->id;

        $defaultLevel = \App\Models\CourseLevel::first();
        $defaultLevelId = $defaultLevel ? $defaultLevel->id : null;

        $defaultExpert = \App\Models\User::role('admin')->first() ?? \App\Models\User::first();
        $defaultExpertId = $defaultExpert ? $defaultExpert->id : 1;

        $imported = 0;
        $skipped = 0;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($rows as $item) {
                $rowStatus = $item['row_status'] ?? 'valid';
                $isDuplicate = !empty($item['is_duplicate']) || $rowStatus === 'duplicate';

                if ($rowStatus === 'invalid' && $skipInvalid) { $skipped++; continue; }
                if ($isDuplicate && $skipDuplicates) { $skipped++; continue; }
                if (empty($item['title'])) { $skipped++; continue; }

                // Category match or creation
                $categoryId = $defaultCategoryId;
                if (!empty($item['category_name'])) {
                    $cat = \App\Models\CourseCategory::where('name', 'like', "%{$item['category_name']}%")->first();
                    if ($cat) {
                        $categoryId = $cat->id;
                    } else {
                        $newCat = \App\Models\CourseCategory::create([
                            'name' => trim($item['category_name']),
                            'slug' => \Illuminate\Support\Str::slug($item['category_name'])
                        ]);
                        $categoryId = $newCat->id;
                    }
                }

                // Level match
                $levelId = $defaultLevelId;
                if (!empty($item['level_title'])) {
                    $lvl = \App\Models\CourseLevel::where('title', 'like', "%{$item['level_title']}%")->first();
                    if ($lvl) $levelId = $lvl->id;
                }

                // Expert/Instructor match
                $expertId = $defaultExpertId;
                if (!empty($item['instructor_name'])) {
                    $user = \App\Models\User::where('first_name', 'like', "%{$item['instructor_name']}%")
                        ->orWhere('last_name', 'like', "%{$item['instructor_name']}%")
                        ->first();
                    if ($user) $expertId = $user->id;
                }

                $statusToApply = $initialStatus;
                if (!empty($item['status']) && in_array($item['status'], ['Published', 'Draft', 'Private', 'Pending Approval', 'Rejected'])) {
                    $statusToApply = $initialStatus === 'Draft' ? 'Draft' : $item['status'];
                }

                $slug = \Illuminate\Support\Str::slug($item['title']);
                $slugCount = Course::where('slug', 'like', "{$slug}%")->count();
                if ($slugCount > 0) $slug = "{$slug}-" . ($slugCount + 1);

                $thumbnail = $item['thumbnail'] ?? null;
                if ($thumbnail && (str_starts_with($thumbnail, 'data:image/') || str_starts_with($thumbnail, 'data:application/'))) {
                    try {
                        if (preg_match('/^data:image\/(\w+);base64,/', $thumbnail, $type)) {
                            $imgData = substr($thumbnail, strpos($thumbnail, ',') + 1);
                            $typeExt = strtolower($type[1]);
                            $decodedBinary = base64_decode($imgData);
                            if ($decodedBinary !== false) {
                                $imgFilename = 'course_thumb_' . uniqid() . '.' . $typeExt;
                                \Illuminate\Support\Facades\Storage::disk('public')->put('courses/thumbnails/' . $imgFilename, $decodedBinary);
                                $thumbnail = 'courses/thumbnails/' . $imgFilename;
                            }
                        }
                    } catch (\Throwable $imgErr) {}
                }

                Course::create([
                    'category_id'       => $categoryId,
                    'level_id'          => $levelId,
                    'expert_id'         => $expertId,
                    'title'             => trim($item['title']),
                    'slug'              => $slug,
                    'short_description' => $item['short_description'] ?? "Master {$item['title']}.",
                    'description'       => $item['description'] ?? "Comprehensive masterclass in {$item['title']}.",
                    'thumbnail'         => $thumbnail,
                    'price'             => is_numeric($item['price'] ?? null) ? (float)$item['price'] : 0,
                    'discount_price'    => is_numeric($item['discount_price'] ?? null) ? (float)$item['discount_price'] : 0,
                    'course_type'       => in_array($item['course_type'] ?? '', ['Free', 'Paid']) ? $item['course_type'] : 'Paid',
                    'language'          => $item['language'] ?? 'English',
                    'duration'          => $item['duration'] ?? '24 Hours',
                    'status'            => $statusToApply,
                    'is_published'      => $statusToApply === 'Published',
                    'is_featured'       => !empty($item['is_featured']),
                    'is_archived'       => false,
                ]);

                $imported++;
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => "Successfully imported {$imported} courses into the database.",
                'imported_count' => $imported,
                'skipped_count'  => $skipped,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Course import failed: ' . $e->getMessage()], 500);
        }
    }
}
