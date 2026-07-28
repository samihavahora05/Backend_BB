<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InternshipApplication;

class StudentInternshipController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $applications = InternshipApplication::where('user_id', $user->id)
            ->with(['internship' => function($q) {
                $q->select('id', 'title', 'company_id', 'location', 'duration', 'stipend', 'skills')
                  ->with('company:id,company_name');
            }])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }
}
