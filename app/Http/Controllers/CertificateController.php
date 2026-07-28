<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Get user's certificates
     */
    public function index(Request $request)
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('course')
            ->latest()
            ->get();
            
        return response()->json($certificates);
    }

    /**
     * Store new certificate (usually admin or automated)
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'grade' => 'nullable|string|max:5',
        ]);

        $certificate = Certificate::create([
            'user_id' => $request->user()->id,
            'course_id' => $request->course_id,
            'credential_id' => 'BB-' . date('Y') . '-' . strtoupper(uniqid()),
            'grade' => $request->grade ?? 'A',
            'issued_at' => now(),
        ]);

        return response()->json($certificate, 201);
    }
}
