<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScholarshipApplication;
use App\Models\ContestSubmission;

class StudentScholarshipController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $scholarships = ScholarshipApplication::where('user_id', $user->id)
            ->with(['program' => function($q) {
                $q->select('id', 'title', 'provider_name', 'amount', 'deadline');
            }])
            ->latest()
            ->get();

        $contests = ContestSubmission::where('user_id', $user->id)
            ->with(['contest' => function($q) {
                $q->select('id', 'title', 'prize', 'end_date');
            }])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'scholarships' => $scholarships,
                'contests' => $contests
            ]
        ]);
    }
}
