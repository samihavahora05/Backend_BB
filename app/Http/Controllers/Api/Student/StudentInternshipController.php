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
            ->with(['internship.company.companyProfile'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }
}
