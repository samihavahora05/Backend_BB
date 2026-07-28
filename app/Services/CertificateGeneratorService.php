<?php

namespace App\Services;

use App\Models\IssuedCertificate;
use App\Models\CertificateSetting;
use App\Models\CertificateTemplate;
use App\Models\CertificateQrCode;
use App\Models\CertificateVerification;
use App\Models\CertificateLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateGeneratorService
{
    /**
     * Issue a new certificate.
     */
    public function issueCertificate(array $data, int $adminId)
    {
        $settings = CertificateSetting::first() ?? CertificateSetting::create([]);
        
        $template = isset($data['template_id']) 
            ? CertificateTemplate::findOrFail($data['template_id'])
            : CertificateTemplate::findOrFail($settings->default_template_id);

        $certNumber = $data['certificate_number'] ?? $this->generateUniqueNumber($settings);
        
        $certificate = IssuedCertificate::create([
            'certificate_number' => $certNumber,
            'user_id' => $data['user_id'],
            'course_id' => $data['course_id'] ?? null,
            'template_id' => $template->id,
            'status' => 'Issued',
            'completion_percentage' => $data['completion_percentage'] ?? null,
            'grade' => $data['grade'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'issued_at' => $data['issued_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? ($settings->expiry_days ? now()->addDays($settings->expiry_days) : null),
        ]);

        if ($settings->enable_verification) {
            $token = Str::random(32);
            CertificateVerification::create([
                'issued_certificate_id' => $certificate->id,
                'verification_token' => $token,
                'verification_url' => config('app.url') . "/verify-certificate/{$token}",
            ]);
        }

        if ($settings->enable_qr_code) {
            $this->generateAndStoreQrCode($certificate);
        }

        $this->generatePdf($certificate);

        CertificateLog::create([
            'issued_certificate_id' => $certificate->id,
            'user_id' => $adminId,
            'action' => 'Issued',
            'description' => "Certificate {$certNumber} issued successfully.",
        ]);

        return $certificate;
    }

    protected function generateUniqueNumber($settings)
    {
        $prefix = $settings->prefix ?? 'CERT-';
        do {
            $number = $prefix . date('Y') . '-' . strtoupper(Str::random(6));
        } while (IssuedCertificate::where('certificate_number', $number)->exists());
        
        return $number;
    }

    protected function generateAndStoreQrCode(IssuedCertificate $certificate)
    {
        // Require simple-qrcode package
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $url = $certificate->verification ? $certificate->verification->verification_url : config('app.url') . '/verify/' . $certificate->certificate_number;
            $image = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate($url);
            $path = 'certificates/qr/' . $certificate->certificate_number . '.png';
            Storage::disk('public')->put($path, $image);
            
            CertificateQrCode::create([
                'issued_certificate_id' => $certificate->id,
                'qr_code_path' => $path,
                'target_url' => $url
            ]);
        }
    }

    public function generatePdf(IssuedCertificate $certificate)
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $certificate->load(['user', 'course', 'template']);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.pdf_template', ['certificate' => $certificate])
                        ->setPaper('a4', 'landscape');
            
            $path = 'certificates/pdf/' . $certificate->certificate_number . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());
            
            $certificate->update(['pdf_path' => $path]);
        }
    }
}
