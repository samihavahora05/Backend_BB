<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = IssuedCertificate::with(['user', 'course']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('course', function($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
        }
        
        $certificates = $query->latest()->get()->map(function($cert) {
            return [
                'id' => $cert->id,
                'student' => $cert->user ? $cert->user->first_name . ' ' . $cert->user->last_name : 'Unknown',
                'course' => $cert->course ? $cert->course->title : 'Unknown',
                'date' => $cert->issued_at ? $cert->issued_at->format('M d, Y') : null,
                'cid' => $cert->certificate_number,
                'status' => $cert->status,
                'file_path' => $cert->pdf_path
            ];
        });

        return response()->json($certificates);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student' => 'required|string',
            'course' => 'required|string',
        ]);

        $user = \App\Models\User::where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $request->student . '%')
            ->orWhere('first_name', 'like', '%' . $request->student . '%')
            ->orWhere('last_name', 'like', '%' . $request->student . '%')
            ->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $course = \App\Models\Course::where('title', 'like', '%' . $request->course . '%')->first();
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $cert = IssuedCertificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_number' => 'CERT-' . mt_rand(1000, 9999) . '-NW',
            'issued_at' => now(),
            'status' => 'Issued'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cert->id,
                'student' => $user->first_name . ' ' . $user->last_name,
                'course' => $course->title,
                'date' => $cert->issued_at->format('M d, Y'),
                'cid' => $cert->certificate_number,
                'status' => $cert->status
            ]
        ], 201);
    }

}
