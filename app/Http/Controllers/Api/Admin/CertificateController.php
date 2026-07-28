<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use App\Services\CertificateGeneratorService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected $generator;

    public function __construct(CertificateGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_issued' => IssuedCertificate::count(),
                'pending' => IssuedCertificate::where('status', 'Pending')->count(),
                'revoked' => IssuedCertificate::where('status', 'Revoked')->count(),
                'downloaded' => \App\Models\CertificateDownload::count(),
                'verified' => \App\Models\CertificateVerification::where('verification_count', '>', 0)->count(),
                'expired' => IssuedCertificate::where('status', 'Expired')->count(),
            ]
        ]);
    }

    public function index(Request $request)
    {
        $query = IssuedCertificate::with(['user', 'course', 'template']);
        
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $certificates = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($certificates);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'template_id' => 'nullable|exists:certificate_templates,id',
            'certificate_number' => 'nullable|string|unique:issued_certificates',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        $certificate = $this->generator->issueCertificate($request->all(), $request->user()->id ?? 1);

        return response()->json(['success' => true, 'data' => $certificate]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Issued,Revoked,Expired,Pending']);
        
        $certificate = IssuedCertificate::findOrFail($id);
        $certificate->update(['status' => $request->status]);

        \App\Models\CertificateLog::create([
            'issued_certificate_id' => $certificate->id,
            'user_id' => $request->user()->id ?? 1,
            'action' => 'Status Updated',
            'description' => "Status changed to {$request->status}",
        ]);

        return response()->json(['success' => true, 'data' => $certificate]);
    }

    public function destroy($id)
    {
        $certificate = IssuedCertificate::findOrFail($id);
        $certificate->delete();

        return response()->json(['success' => true, 'message' => 'Certificate deleted']);
    }
}
