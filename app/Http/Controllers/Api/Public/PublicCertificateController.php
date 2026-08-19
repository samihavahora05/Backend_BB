<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicCertificateController extends Controller
{
    protected $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Verify a certificate by number or ID
     * GET /api/public/certificates/{certificate_number}/verify
     */
    public function verify($certificate_number)
    {
        $numericId = is_numeric($certificate_number) ? (int)$certificate_number : (int)preg_replace('/[^0-9]/', '', $certificate_number);

        $cert = IssuedCertificate::with(['user', 'course', 'template'])
            ->where('certificate_number', $certificate_number)
            ->orWhere('id', $certificate_number)
            ->orWhere('id', $numericId)
            ->first();

        if (!$cert) {
            return response()->json(['success' => false, 'message' => 'Certificate not found.'], 404);
        }

        $studentName = $cert->student_name ?: ($cert->user ? $cert->user->name : 'Student');
        $courseName  = $cert->course_title ?: ($cert->course ? $cert->course->title : 'Certificate of Completion');

        return response()->json([
            'success' => true,
            'data'    => [
                'certificate_number' => $cert->certificate_number ?: ('CERT-' . $cert->id),
                'student_name'       => $studentName,
                'course_name'        => $courseName,
                'template'           => $cert->template ? $cert->template->title : 'Standard Template',
                'issued_at'          => $cert->issued_at ? $cert->issued_at->format('F j, Y') : date('F j, Y'),
                'status'             => $cert->status,
                'download_url'       => url("/api/public/certificates/" . ($cert->certificate_number ?: $cert->id) . "/download")
            ]
        ]);
    }

    /**
     * Generate & Download Certificate PDF
     * GET /api/public/certificates/{certificate_number}/download
     */
    public function download($certificate_number)
    {
        try {
            $numericId = is_numeric($certificate_number) ? (int)$certificate_number : (int)preg_replace('/[^0-9]/', '', $certificate_number);

            $cert = IssuedCertificate::with(['user', 'course', 'template'])
                ->where('certificate_number', $certificate_number)
                ->orWhere('id', $certificate_number)
                ->orWhere('id', $numericId)
                ->first();

            if (!$cert) {
                return response()->json(['success' => false, 'message' => 'Certificate not found'], 404);
            }

            $pdf = $this->pdfService->generate($cert);
            return $pdf->download('Certificate_' . $cert->certificate_number . '.pdf');

        } catch (\Throwable $e) {
            Log::error('PublicCertificateController download error', [
                'certificate_number' => $certificate_number,
                'error'              => $e->getMessage(),
                'file'               => $e->getFile(),
                'line'               => $e->getLine()
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF generation failed: ' . $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine()
                ], 500);
            }

            return response()->json(['success' => false, 'message' => 'Failed to generate certificate PDF.'], 500);
        }
    }

    public function templateBackground($filename)
    {
        $path = storage_path('app/public/certificates/templates/' . $filename);
        if (!file_exists($path)) {
            $path = public_path('storage/certificates/templates/' . $filename);
        }
        if (!file_exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $mime = mime_content_type($path) ?: 'image/png';
        if (str_ends_with($filename, '.svg')) {
            $mime = 'image/svg+xml';
        }

        return response()->file($path, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Content-Type'                 => $mime,
        ]);
    }
}
