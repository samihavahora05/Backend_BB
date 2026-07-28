<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IssuedCertificate;
use App\Models\CourseEnrollment;

class StudentCertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $certificates = IssuedCertificate::where('user_id', $user->id)
            ->with(['course' => function($q) {
                $q->select('id', 'title', 'thumbnail');
            }])
            ->latest()
            ->get();

        $inProgress = CourseEnrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereColumn('completed_lessons', '<', 'total_lessons')
            ->with(['course' => function($q) {
                $q->select('id', 'title', 'thumbnail');
            }])
            ->latest()
            ->get()
            ->map(function ($enrollment) {
                $progress = $enrollment->total_lessons > 0 
                    ? round(($enrollment->completed_lessons / $enrollment->total_lessons) * 100) 
                    : 0;
                
                return [
                    'id' => $enrollment->id,
                    'course' => $enrollment->course,
                    'progress' => $progress
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'earned' => $certificates,
                'in_progress' => $inProgress
            ]
        ]);
    }
}
