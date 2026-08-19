<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use App\Services\CertificateIssueService;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminCertificateController extends Controller
{
    protected $issueService;
    protected $pdfService;

    public function __construct(CertificateIssueService $issueService, CertificatePdfService $pdfService)
    {
        $this->issueService = $issueService;
        $this->pdfService   = $pdfService;
    }

    public function index(Request $request)
    {
        $query = IssuedCertificate::with(['user', 'course', 'template']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%")
                  ->orWhere('course_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('course', function($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
        }
        
        $certificates = $query->latest()->get()->map(function($cert) {
            $student = $cert->student_name ?: ($cert->user ? trim($cert->user->first_name . ' ' . $cert->user->last_name) : 'Unknown');
            $course = $cert->course_title ?: ($cert->course ? $cert->course->title : 'Unknown');
            $certNum = $cert->certificate_number ?: ('CERT-' . $cert->id);
            $downloadUrl = url("/api/public/certificates/{$certNum}/download");
            
            $template = $cert->template;
            $bg = $template ? $template->background_image_path : null;
            if ($bg && !str_starts_with($bg, 'http')) {
                $bg = url('api/public/templates/background/' . basename($bg));
            }

            return [
                'id'                 => $cert->id,
                'template_id'        => $cert->template_id,
                'template'           => $template,
                'bg_image'           => $bg,
                'background_image'   => $bg,
                'student'            => $student,
                'student_name'       => $student,
                'course'             => $course,
                'course_title'       => $course,
                'date'               => $cert->issued_at ? $cert->issued_at->format('M d, Y') : null,
                'cid'                => $certNum,
                'certificate_number' => $certNum,
                'status'             => $cert->status,
                'file_path'          => $cert->pdf_path ?: $downloadUrl,
                'download_url'       => $downloadUrl
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $certificates
        ]);
    }

    public function store(Request $request)
    {
        try {
            $cert = $this->issueService->issue($request->all());

            $downloadUrl = url("/api/public/certificates/{$cert->certificate_number}/download");
            $template = $cert->template;
            $bg = $template ? $template->background_image_path : null;
            if ($bg && !str_starts_with($bg, 'http')) {
                $bg = url('api/public/templates/background/' . basename($bg));
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'                 => $cert->id,
                    'template_id'        => $cert->template_id,
                    'template'           => $template,
                    'bg_image'           => $bg,
                    'background_image'   => $bg,
                    'student_name'       => $cert->student_name,
                    'student'            => $cert->student_name,
                    'course'             => $cert->course_title,
                    'course_title'       => $cert->course_title,
                    'date'               => $cert->issued_at ? $cert->issued_at->format('M d, Y') : date('M d, Y'),
                    'cid'                => $cert->certificate_number,
                    'certificate_number' => $cert->certificate_number,
                    'status'             => $cert->status,
                    'file_path'          => $cert->pdf_path ?: $downloadUrl,
                    'download_url'       => $downloadUrl
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => ['student' => [$e->getMessage()]]
            ], 422);
        } catch (\Throwable $e) {
            Log::error('AdminCertificateController store exception', [
                'request' => $request->all(),
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine()
                ], 500);
            }

            return response()->json(['success' => false, 'message' => 'Failed to issue certificate'], 500);
        }
    }

    public function download($id)
    {
        $numericId = is_numeric($id) ? (int)$id : (int)preg_replace('/[^0-9]/', '', $id);
        $cert = IssuedCertificate::where('id', $id)
            ->orWhere('id', $numericId)
            ->orWhere('certificate_number', $id)
            ->firstOrFail();

        $pdf = $this->pdfService->generate($cert);
        return $pdf->download('Certificate_' . $cert->certificate_number . '.pdf');
    }
}
