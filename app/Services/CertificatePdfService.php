<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificatePdfService
{
    /**
     * Generate PDF for an IssuedCertificate and return DomPDF instance.
     */
    public function generate(IssuedCertificate $cert)
    {
        $cert->loadMissing(['user', 'course', 'template']);

        $template = $cert->template;
        if (!$template && $cert->template_id) {
            $template = CertificateTemplate::find($cert->template_id);
        }

        if (!$template) {
            throw new \RuntimeException("Certificate template ID {$cert->template_id} not found in database for certificate {$cert->certificate_number}");
        }

        Log::info('CERTIFICATE TEMPLATE DEBUG', [
            'certificate_number'    => $cert->certificate_number,
            'template_id'           => $cert->template_id,
            'loaded_template_db_id' => $template->id,
            'template_title'        => $template->title,
            'background_image_path' => $template->background_image_path,
            'layout_settings'       => $template->layout_settings,
        ]);

        $pdfPath = 'certificates/' . $cert->certificate_number . '.pdf';

        // Generate base64 QR Code
        $verifyUrl = url("/verify-certificate/{$cert->certificate_number}");
        $qrCode = '';
        try {
            if (class_exists(QrCode::class)) {
                $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate($verifyUrl));
            }
        } catch (\Throwable $e) {
            Log::warning('Certificate QR Code generation warning: ' . $e->getMessage(), ['cert_number' => $cert->certificate_number]);
        }

        $studentName = $cert->student_name ?: ($cert->user ? (trim($cert->user->first_name . ' ' . $cert->user->last_name) ?: $cert->user->name) : 'Student');
        $courseName  = $cert->course_title ?: ($cert->course ? $cert->course->title : 'Certificate of Completion');

        $templateBgData = null;
        if ($template->background_image_path) {
            $path = $template->background_image_path;
            $fullPath = storage_path('app/public/' . $path);
            if (!file_exists($fullPath)) {
                $fullPath = public_path('storage/' . $path);
            }
            if (file_exists($fullPath) && is_file($fullPath)) {
                $mime = mime_content_type($fullPath) ?: 'image/svg+xml';
                if (str_ends_with($fullPath, '.svg')) {
                    $mime = 'image/svg+xml';
                }
                $templateBgData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            } else if (str_starts_with($path, 'http')) {
                $templateBgData = $path;
            }
        }

        $layout = is_array($template->layout_settings) ? $template->layout_settings : (json_decode($template->layout_settings, true) ?? []);

        $data = [
            'template'     => $template,
            'cert_title'   => $layout['title'] ?? $template->title ?? 'Certificate of Completion',
            'show_title'   => $layout['showTitle'] ?? true,
            'elements'     => $layout['elements'] ?? [],
            'student_name' => $studentName,
            'course_name'  => $courseName,
            'date'         => $cert->issued_at ? $cert->issued_at->format('F j, Y') : date('F j, Y'),
            'cert_id'      => $cert->certificate_number,
            'qr_code'      => $qrCode,
            'template_bg'  => $templateBgData,
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data)
                  ->setPaper('a4', 'landscape')
                  ->setWarnings(false);

        // Store PDF in public disk
        try {
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $cert->update(['pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::error('Failed to save certificate PDF file', [
                'cert_number' => $cert->certificate_number,
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine()
            ]);
        }

        return $pdf;
    }
}
