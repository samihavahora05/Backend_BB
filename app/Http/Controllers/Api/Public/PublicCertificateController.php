<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PublicCertificateController extends Controller
{
    /**
     * Verify a certificate by its unique number
     * GET /api/public/certificates/{certificate_number}/verify
     */
    public function verify($certificate_number)
    {
        $cert = IssuedCertificate::with(['user', 'course'])
            ->where('certificate_number', $certificate_number)
            ->first();

        if (!$cert) {
            return response()->json(['success' => false, 'message' => 'Certificate not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'certificate_number' => $cert->certificate_number,
                'student_name'       => $cert->user->name,
                'course_name'        => $cert->course->title,
                'issued_at'          => $cert->issued_at->format('F j, Y'),
                'status'             => $cert->status,
                'download_url'       => url("/api/public/certificates/{$certificate_number}/download")
            ]
        ]);
    }

    /**
     * Generate & Download the Certificate PDF
     * GET /api/public/certificates/{certificate_number}/download
     */
    public function download($certificate_number)
    {
        $cert = IssuedCertificate::with(['user', 'course'])->where('certificate_number', $certificate_number)->firstOrFail();

        // High Performance UX: Check if the PDF already exists in storage to avoid re-generating
        $pdfPath = 'certificates/' . $cert->certificate_number . '.pdf';
        
        if (Storage::disk('public')->exists($pdfPath)) {
            return response()->file(storage_path('app/public/' . $pdfPath));
        }

        // Generate QR Code containing the verification link
        $verifyUrl = url("/verify-certificate/{$cert->certificate_number}");
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate($verifyUrl));

        // Prepare data for the view
        $data = [
            'student_name' => $cert->user->name,
            'course_name'  => $cert->course->title,
            'date'         => $cert->issued_at->format('F j, Y'),
            'cert_id'      => $cert->certificate_number,
            'qr_code'      => $qrCode,
            // Assuming there's a default background template, or pass a base64 encoded bg image for DomPDF
        ];

        // Ensure you have a 'resources/views/pdf/certificate.blade.php' file
        // For performance, use setOptions to disable remote font loading if not needed
        $pdf = Pdf::loadView('pdf.certificate', $data)
                  ->setPaper('a4', 'landscape')
                  ->setWarnings(false);
        
        // Save to storage for future instant access
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdf->download('Certificate_' . $cert->certificate_number . '.pdf');
    }
}
