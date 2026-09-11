<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Http\Resources\InstructorListResource;
use App\Http\Resources\InstructorDetailResource;
use App\Models\ExpertProfile;
use App\Models\User;
use App\Exports\ExpertsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminInstructorController extends Controller
{
    protected $repository;

    public function __construct(InstructorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        if ($request->filled('status') && !$request->filled('approval_status')) {
            $filters['approval_status'] = $request->get('status');
        }

        $instructors = $this->repository->getAllInstructors($filters, $request->get('per_page', 15));
        return InstructorListResource::collection($instructors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        
        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $instructor = $this->repository->createInstructor($data);
        $instructorWithProfile = $this->repository->getInstructorById($instructor->id);
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructorWithProfile)], 201);
    }

    public function show($id)
    {
        $instructor = $this->repository->getInstructorById($id);
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructor)]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $instructor = $this->repository->updateInstructor((int)$id, $data);
        if ($instructor->user) {
            $instructor->loadMissing(['user.expertSkills', 'user.expertDocuments', 'user.expertLanguages', 'user.expertCertificates']);
        }
        return response()->json(['success' => true, 'data' => new InstructorDetailResource($instructor)]);
    }

    public function destroy($id)
    {
        try {
            $profile = ExpertProfile::where('id', $id)
                ->orWhere('user_id', $id)
                ->first();
            $userId = $profile ? $profile->user_id : (int)$id;
            $profileId = $profile ? $profile->id : (int)$id;

            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

            DB::transaction(function () use ($userId, $profileId) {
                try { DB::table('expert_availabilities')->where('expert_profile_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_bookings')->where('expert_profile_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_course_assignments')->where('expert_id', $userId)->orWhere('expert_id', $profileId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_activity_logs')->where('expert_id', $userId)->orWhere('admin_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_reviews')->where('expert_id', $userId)->orWhere('student_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_certificates')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_languages')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_documents')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('expert_skills')->where('user_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('mentor_sessions')->where('expert_id', $profileId)->orWhere('expert_profile_id', $profileId)->orWhere('expert_id', $userId)->delete(); } catch (\Throwable $t) {}
                try { DB::table('mentor_bookings')->where('expert_id', $profileId)->orWhere('expert_id', $userId)->delete(); } catch (\Throwable $t) {}
                
                try {
                    $adminUser = User::role('super_admin')->first() ?? User::role('admin')->first();
                    if ($adminUser) {
                        DB::table('courses')->where('expert_id', $userId)->orWhere('expert_id', $profileId)->update(['expert_id' => $adminUser->id]);
                    }
                } catch (\Throwable $t) {}

                try {
                    DB::table('expert_profiles')->where('id', $profileId)->orWhere('user_id', $userId)->delete();
                } catch (\Throwable $t) {}

                $user = User::withTrashed()->where('id', $userId)->first();
                if ($user) {
                    try {
                        if (method_exists($user, 'roles')) {
                            $user->roles()->detach();
                        }
                    } catch (\Throwable $t) {}
                    try {
                        $user->forceDelete();
                    } catch (\Throwable $t) {
                        DB::table('users')->where('id', $userId)->delete();
                    }
                }
            });

            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            Cache::flush();
            return response()->json(['success' => true, 'message' => 'Instructor deleted permanently']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            Log::error('Instructor delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export Experts directly into an Excel (.xlsx) spreadsheet
     * GET /api/admin/instructors/export
     */
    public function export(Request $request)
    {
        try {
            $profiles = ExpertProfile::whereHas('user', function($q) {
                $q->whereNull('deleted_at');
            })->with(['user'])->latest()->get();

            $dateStr = now()->format('Y-m-d');
            $fileName = "experts_export_{$dateStr}.xlsx";

            return \Maatwebsite\Excel\Facades\Excel::download(new ExpertsExport($profiles), $fileName);
        } catch (\Throwable $e) {
            Log::error('Expert export failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to export experts: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download Import Template directly as an Excel (.xlsx) spreadsheet
     * GET /api/admin/instructors/sample-template
     */
    public function sampleTemplate(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // Sheet 1: Template
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Experts Template');

            $headers = [
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Designation',
                'Company',
                'Specialization',
                'Hourly Rate (INR)',
                'Expert Photo',
                'Password',
            ];

            foreach ($headers as $index => $header) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
                $sheet->setCellValue($cellCoord, $header);
            }

            $sheet->getStyle('A1:J1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B2A6B']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);

            $sampleRows = [
                ['Aarav', 'Sharma', 'aarav.sharma@example.com', '9876543210', 'Principal Architect', 'CloudTech Labs', 'Cloud Computing & DevOps', 2500, '', 'Password@123'],
                ['Priya', 'Patel', 'priya.patel@example.com', '9876543211', 'Lead AI Researcher', 'NeuroVision AI', 'Machine Learning & AI', 3000, '', 'Password@123'],
                ['Rohan', 'Mehta', 'rohan.mehta@example.com', '9876543212', 'Senior UI/UX Designer', 'PixelCraft Studio', 'Product Design & Figma', 1800, '', 'Password@123'],
            ];

            foreach ($sampleRows as $rowIndex => $rowData) {
                $rowNum = $rowIndex + 2;
                foreach ($rowData as $colIndex => $val) {
                    $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $rowNum;
                    $sheet->setCellValue($cellCoord, $val);
                }
                $sheet->getRowDimension($rowNum)->setRowHeight(22);
            }

            for ($i = 1; $i <= count($headers); $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Sheet 2: Instructions
            $instructionsSheet = $spreadsheet->createSheet();
            $instructionsSheet->setTitle('Instructions');
            $instructionsSheet->setCellValue('A1', 'EXPERT IMPORT INSTRUCTIONS');
            $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1B2A6B'));

            $instructions = [
                '1. Required Columns: First Name, Email, Designation, Company, Specialization.',
                '2. Email must be unique. If an existing Expert with this email is found, their profile details will be updated.',
                '3. Hourly Rate: Enter standard numbers (e.g. 1500, 2000).',
                '4. Profile Photo: Can be a URL, base64 data URI, or left blank to be updated later by Admin or the Expert.',
                '5. Once imported and approved, the Expert can log into their Expert Dashboard and complete their profile, including uploading their photo.',
            ];

            foreach ($instructions as $idx => $line) {
                $instructionsSheet->setCellValue('A' . ($idx + 3), $line);
            }
            $instructionsSheet->getColumnDimension('A')->setWidth(100);

            $writer = new Xlsx($spreadsheet);
            $excelTemp = tempnam(sys_get_temp_dir(), 'exp_tmpl_');
            $writer->save($excelTemp);

            return response()->download($excelTemp, 'experts_import_template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            Log::error('Template download error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate template: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Preview and Validate Uploaded ZIP / Excel for Experts Import
     * POST /api/admin/instructors/import/preview
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        $extractedImages = []; // [ filename => base64_data_uri ]
        $rawMatrix = [];

        try {
            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($realPath) !== true) {
                    return response()->json(['success' => false, 'message' => 'Could not read or extract the uploaded ZIP package.'], 422);
                }

                $excelContent = null;
                // Read all files in ZIP safely
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    // Prevent zip-slip
                    if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                        continue;
                    }

                    $baseEntry = basename($entryName);
                    $entryExt = strtolower(pathinfo($baseEntry, PATHINFO_EXTENSION));

                    // Check for Excel file
                    if (in_array($entryExt, ['xlsx', 'xls']) && $excelContent === null) {
                        $excelContent = $zip->getFromIndex($i);
                    }

                    // Check for Images in images/ or root
                    if (in_array($entryExt, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $imgStream = $zip->getFromIndex($i);
                        if ($imgStream && strlen($imgStream) <= 10485760) { // 10MB max per image
                            $mime = 'image/' . ($entryExt === 'jpg' ? 'jpeg' : $entryExt);
                            $extractedImages[$baseEntry] = 'data:' . $mime . ';base64,' . base64_encode($imgStream);
                        }
                    }
                }
                $zip->close();

                if (!$excelContent) {
                    return response()->json(['success' => false, 'message' => 'No valid Excel file (experts.xlsx) found inside the ZIP package.'], 422);
                }

                $tempExcel = tempnam(sys_get_temp_dir(), 'prev_exp_');
                file_put_contents($tempExcel, $excelContent);
                $spreadsheet = IOFactory::load($tempExcel);
                $rawMatrix = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                @unlink($tempExcel);

            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                $spreadsheet = IOFactory::load($realPath);
                $rawMatrix = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } else {
                return response()->json(['success' => false, 'message' => 'Unsupported file format. Please upload a .zip or .xlsx file.'], 422);
            }

            if (empty($rawMatrix) || count($rawMatrix) < 2) {
                return response()->json(['success' => false, 'message' => 'The uploaded file is empty or does not contain data rows.'], 422);
            }

            // Parse headers
            $headers = array_map(function($h) {
                return strtolower(trim((string)$h));
            }, $rawMatrix[0]);

            $colMap = [
                'first_name' => $this->findHeaderIndex($headers, ['first name', 'firstname', 'name']),
                'last_name' => $this->findHeaderIndex($headers, ['last name', 'lastname', 'surname']),
                'email' => $this->findHeaderIndex($headers, ['email', 'email address']),
                'phone' => $this->findHeaderIndex($headers, ['phone', 'mobile', 'contact']),
                'designation' => $this->findHeaderIndex($headers, ['designation', 'title', 'role', 'job title']),
                'company' => $this->findHeaderIndex($headers, ['company', 'organization']),
                'specialization' => $this->findHeaderIndex($headers, ['specialization', 'expertise', 'domain', 'subject']),
                'hourly_rate' => $this->findHeaderIndex($headers, ['hourly rate', 'rate', 'price', 'hourly_rate (inr)', 'hourly rate (inr)']),
                'photo' => $this->findHeaderIndex($headers, ['expert photo', 'photo', 'avatar', 'profile photo', 'image']),
                'password' => $this->findHeaderIndex($headers, ['password']),
            ];

            if ($colMap['email'] === -1) {
                return response()->json(['success' => false, 'message' => 'Missing required column "Email" in Excel.'], 422);
            }

            $existingUsers = User::with('expertProfile')->get()->keyBy(function($u) {
                return strtolower(trim($u->email));
            });

            $previewRows = [];
            $validCount = 0;
            $warningCount = 0;
            $errorCount = 0;

            for ($r = 1; $r < count($rawMatrix); $r++) {
                $row = $rawMatrix[$r];
                if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== ''))) {
                    continue;
                }

                $email = strtolower(trim((string)($row[$colMap['email']] ?? '')));
                $firstName = trim((string)($colMap['first_name'] >= 0 ? ($row[$colMap['first_name']] ?? '') : ''));
                $lastName = trim((string)($colMap['last_name'] >= 0 ? ($row[$colMap['last_name']] ?? '') : ''));
                $phone = trim((string)($colMap['phone'] >= 0 ? ($row[$colMap['phone']] ?? '') : ''));
                $designation = trim((string)($colMap['designation'] >= 0 ? ($row[$colMap['designation']] ?? '') : 'Expert'));
                $company = trim((string)($colMap['company'] >= 0 ? ($row[$colMap['company']] ?? '') : 'Independent'));
                $specialization = trim((string)($colMap['specialization'] >= 0 ? ($row[$colMap['specialization']] ?? '') : 'Career & Technical Mentorship'));
                $rateRaw = $colMap['hourly_rate'] >= 0 ? ($row[$colMap['hourly_rate']] ?? 1500) : 1500;
                $hourlyRate = is_numeric($rateRaw) && (float)$rateRaw > 0 ? (float)$rateRaw : 1500.0;
                $photoFileName = trim((string)($colMap['photo'] >= 0 ? ($row[$colMap['photo']] ?? '') : ''));
                $password = trim((string)($colMap['password'] >= 0 ? ($row[$colMap['password']] ?? '') : 'Password@123'));

                $rowStatus = 'valid';
                $issues = [];
                $action = 'create';
                $existingUser = $existingUsers->get($email);

                if ($existingUser) {
                    $action = 'update';
                }

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowStatus = 'error';
                    $issues[] = 'Invalid or missing email address.';
                }

                if (empty($firstName) && !$existingUser) {
                    $firstName = 'Expert';
                }

                // Match Image
                $photoPreview = null;
                $imageStatus = 'none';

                if (!empty($photoFileName)) {
                    $basePhoto = basename($photoFileName);
                    if (isset($extractedImages[$basePhoto])) {
                        $photoPreview = $extractedImages[$basePhoto];
                        $imageStatus = 'matched';
                    } elseif (isset($extractedImages[strtolower($basePhoto)])) {
                        $photoPreview = $extractedImages[strtolower($basePhoto)];
                        $imageStatus = 'matched';
                    } else {
                        $imageStatus = 'missing';
                        $issues[] = "Image not found in package: {$photoFileName}";
                        if ($rowStatus === 'valid') {
                            $rowStatus = 'warning';
                        }
                    }
                } elseif ($existingUser && $existingUser->expertProfile && $existingUser->expertProfile->profile_photo) {
                    $photoPreview = $existingUser->expertProfile->profile_photo;
                    $imageStatus = 'existing_kept';
                }

                if ($rowStatus === 'valid') $validCount++;
                elseif ($rowStatus === 'warning') $warningCount++;
                else $errorCount++;

                $previewRows[] = [
                    'row_num' => $r + 1,
                    'first_name' => $firstName ?: ($existingUser ? $existingUser->first_name : 'Expert'),
                    'last_name' => $lastName ?: ($existingUser ? $existingUser->last_name : ''),
                    'name' => trim($firstName . ' ' . $lastName) ?: ($existingUser ? $existingUser->name : 'Expert'),
                    'email' => $email,
                    'phone' => $phone ?: ($existingUser ? $existingUser->phone : ''),
                    'designation' => $designation ?: 'Expert',
                    'company' => $company ?: 'Independent',
                    'specialization' => $specialization ?: 'Career & Technical Mentorship',
                    'hourly_rate' => $hourlyRate,
                    'photo_filename' => $photoFileName,
                    'photo_preview' => $photoPreview,
                    'image_status' => $imageStatus,
                    'password' => $password ?: 'Password@123',
                    'action' => $action,
                    'status' => $rowStatus,
                    'issues' => $issues,
                ];
            }

            return response()->json([
                'success' => true,
                'total_rows' => count($previewRows),
                'valid_rows' => $validCount,
                'warning_rows' => $warningCount,
                'error_rows' => $errorCount,
                'data' => $previewRows,
            ]);

        } catch (\Throwable $e) {
            Log::error('Import preview failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to parse import file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm and Execute Expert Import
     * POST /api/admin/instructors/import/confirm
     */
    public function confirmImport(Request $request)
    {
        $request->validate([
            'rows' => 'required|array|min:1',
        ]);

        $rows = $request->input('rows');
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (($row['status'] ?? '') === 'error') {
                    $skippedCount++;
                    continue;
                }

                $email = strtolower(trim((string)($row['email'] ?? '')));
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skippedCount++;
                    continue;
                }

                $firstName = trim((string)($row['first_name'] ?? 'Expert'));
                $lastName = trim((string)($row['last_name'] ?? ''));
                $fullName = trim($firstName . ' ' . $lastName) ?: 'Expert';
                $phone = trim((string)($row['phone'] ?? ''));
                $designation = trim((string)($row['designation'] ?? 'Expert'));
                $company = trim((string)($row['company'] ?? 'Independent'));
                $specialization = trim((string)($row['specialization'] ?? 'Career & Technical Mentorship'));
                $hourlyRate = (float)($row['hourly_rate'] ?? 1500.0);
                $password = !empty($row['password']) ? $row['password'] : 'Password@123';
                $photoPreview = $row['photo_preview'] ?? null;

                // Handle Photo Storage if base64 data URI provided
                $storedPhotoUrl = null;
                if ($photoPreview && str_starts_with($photoPreview, 'data:image')) {
                    $imageParts = explode(';base64,', $photoPreview);
                    if (count($imageParts) === 2) {
                        $imageType = explode('/', $imageParts[0])[1] ?? 'jpg';
                        if (str_contains($imageType, ';')) $imageType = explode(';', $imageType)[0];
                        if ($imageType === 'jpeg') $imageType = 'jpg';
                        $imageDecoded = base64_decode($imageParts[1]);
                        
                        $fileName = 'avatars/' . uniqid('exp_', true) . '.' . $imageType;
                        Storage::disk('public')->put($fileName, $imageDecoded);
                        $storedPhotoUrl = '/storage/' . $fileName;
                    }
                }

                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing
                    $userUpdates = [
                        'name' => $fullName,
                    ];
                    if (!empty($firstName)) $userUpdates['first_name'] = $firstName;
                    if (!empty($lastName)) $userUpdates['last_name'] = $lastName;
                    if (!empty($phone)) $userUpdates['phone'] = $phone;
                    $user->update($userUpdates);

                    $profile = $user->expertProfile;
                    $profileUpdates = [
                        'designation' => $designation,
                        'company' => $company,
                        'specialization' => $specialization,
                        'hourly_rate' => $hourlyRate,
                        'approval_status' => 'approved',
                        'is_verified' => true,
                    ];

                    if ($storedPhotoUrl) {
                        $profileUpdates['profile_photo'] = $storedPhotoUrl;
                    }

                    if ($profile) {
                        $profile->update($profileUpdates);
                    } else {
                        $profileUpdates['average_rating'] = 5.0;
                        $profileUpdates['total_reviews'] = 0;
                        $profileUpdates['is_available'] = true;
                        $user->expertProfile()->create($profileUpdates);
                    }

                    $updatedCount++;
                } else {
                    // Create new
                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'name' => $fullName,
                        'email' => $email,
                        'phone' => $phone ?: null,
                        'password' => Hash::make($password),
                        'status' => 'active',
                    ]);

                    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'expert', 'guard_name' => 'web']);
                    $user->assignRole($role);

                    $user->expertProfile()->create([
                        'designation' => $designation,
                        'company' => $company,
                        'specialization' => $specialization,
                        'hourly_rate' => $hourlyRate,
                        'profile_photo' => $storedPhotoUrl,
                        'approval_status' => 'approved',
                        'is_verified' => true,
                        'is_available' => true,
                        'average_rating' => 5.0,
                        'total_reviews' => 0,
                    ]);

                    $createdCount++;
                }
            }

            DB::commit();
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => "Import completed successfully! Created: {$createdCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}",
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Import confirmation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Resolve image bytes from local disk, public directory or URL
     */
    protected function resolveImageBytes(?string $rawPath): ?string
    {
        if (empty($rawPath)) return null;

        // If local /storage path
        if (str_contains($rawPath, '/storage/')) {
            $relativePath = ltrim(substr($rawPath, strpos($rawPath, '/storage/') + 9), '/');
            if (Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->get($relativePath);
            }
        }

        // Direct disk path
        if (Storage::disk('public')->exists($rawPath)) {
            return Storage::disk('public')->get($rawPath);
        }

        // Relative public path
        $cleanPath = ltrim(str_replace('\\', '/', $rawPath), '/');
        $publicFile = public_path($cleanPath);
        if (file_exists($publicFile) && is_file($publicFile)) {
            return file_get_contents($publicFile);
        }

        // If absolute URL
        if (filter_var($rawPath, FILTER_VALIDATE_URL)) {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $data = @file_get_contents($rawPath, false, $ctx);
                if ($data !== false) return $data;
            } catch (\Throwable $t) {}
        }

        return null;
    }

    protected function findHeaderIndex(array $headers, array $candidates): int
    {
        foreach ($headers as $index => $h) {
            foreach ($candidates as $c) {
                if ($h === $c || str_contains($h, $c)) {
                    return $index;
                }
            }
        }
        return -1;
    }
}
