<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\IssuedCertificate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertificateIssueService
{
    protected $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Issue a new certificate.
     */
    public function issue(array $data): IssuedCertificate
    {
        $studentName   = trim($data['student_name'] ?? $data['student'] ?? '');
        $studentEmail  = trim($data['student_email'] ?? '');
        $courseTitle   = trim($data['course_title'] ?? $data['course'] ?? '');
        $rawTemplateId = $data['template_id'] ?? null;

        if (empty($studentName)) {
            throw new \InvalidArgumentException('Student name is required');
        }

        // 1. Student Lookup or Creation
        $user = null;
        if (!empty($studentEmail)) {
            $user = User::where('email', $studentEmail)->first();
        }
        if (!$user) {
            $user = User::where(function($query) use ($studentName) {
                $query->where('first_name', 'like', "%{$studentName}%")
                      ->orWhere('last_name', 'like', "%{$studentName}%");
                
                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlite' || $driver === 'pgsql') {
                    $query->orWhereRaw("(first_name || ' ' || last_name) LIKE ?", ["%{$studentName}%"]);
                } else {
                    $query->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$studentName}%"]);
                }
            })->first();
        }
        if (!$user) {
            $user = User::create([
                'name'     => $studentName,
                'email'    => $studentEmail ?: ('student_' . time() . rand(10,99) . '@blueboxx.in'),
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        // 2. Course Lookup (Nullable if not found)
        $course = null;
        if (!empty($courseTitle)) {
            $course = Course::where('title', 'like', "%{$courseTitle}%")->first();
        }

        // 3. Strict Template Resolution
        $template = null;

        if (!empty($rawTemplateId)) {
            if (is_numeric($rawTemplateId)) {
                $template = CertificateTemplate::find($rawTemplateId);
            }
            if (!$template) {
                $template = CertificateTemplate::where('title', 'like', "%{$rawTemplateId}%")->first();
            }
        }

        $templateTitle = trim($data['template_title'] ?? $data['title'] ?? '');
        if (!$template && !empty($templateTitle)) {
            $template = CertificateTemplate::where('title', 'like', "%{$templateTitle}%")->first();
        }

        if (!$template && !empty($courseTitle)) {
            $template = CertificateTemplate::where('title', 'like', "%{$courseTitle}%")->first();
            if (!$template && (stripos($courseTitle, 'ai') !== false || stripos($courseTitle, 'workshop') !== false)) {
                $template = CertificateTemplate::where('title', 'like', '%AI%')->first();
            }
        }

        // If template still not found, create a new record safely
        if (!$template) {
            $titleToUse = !empty($templateTitle) ? $templateTitle : (!empty($courseTitle) ? $courseTitle . ' Template' : 'Standard Certificate Template');
            $template = CertificateTemplate::create([
                'title'                 => $titleToUse,
                'background_image_path' => 'certificates/templates/default_template.svg',
                'layout_settings'       => [
                    'title'     => 'Certificate of Completion',
                    'showTitle' => true,
                ]
            ]);
        }

        Log::info('CERTIFICATE ISSUATION DEBUG', [
            'raw_template_id'       => $rawTemplateId,
            'resolved_template_id'  => $template->id,
            'template_title'        => $template->title,
            'background_image_path' => $template->background_image_path
        ]);

        // 4. Generate Unique Certificate Number
        $certNumber = 'CERT-' . mt_rand(100000, 999999) . '-BB';
        while (IssuedCertificate::where('certificate_number', $certNumber)->exists()) {
            $certNumber = 'CERT-' . mt_rand(100000, 999999) . '-BB';
        }

        // 5. Create IssuedCertificate
        $cert = IssuedCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course ? $course->id : null,
            'template_id'        => $template->id,
            'student_name'       => $studentName,
            'student_email'      => $studentEmail,
            'course_title'       => $courseTitle ?: 'Certificate of Completion',
            'certificate_number' => $certNumber,
            'issued_at'          => now(),
            'status'             => 'Issued'
        ]);

        // 6. Generate & Save PDF
        try {
            $this->pdfService->generate($cert);
        } catch (\Throwable $e) {
            Log::error('PDF generation error during certificate issuance', [
                'cert_number' => $certNumber,
                'template_id' => $template->id,
                'student'     => $studentName,
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine()
            ]);
        }

        return $cert->fresh(['user', 'course', 'template']);
    }
}
