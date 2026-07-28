<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentEducation;
use App\Models\Job;
use App\Models\Internship;

class CollegeDashboardController extends Controller
{
    /**
     * Get dashboard KPIs and lists
     */
    public function index(Request $request)
    {
        $college = $request->user();

        // Total students linked to this college
        $totalStudents = StudentEducation::where('college_id', $college->id)->count();

        // Active placement drives by this college
        $activePlacementDrives = Job::where('college_id', $college->id)
            ->where('drive_type', 'placement_drive')
            ->where('status', 'active')
            ->count();

        // Active internship drives by this college
        $activeInternshipDrives = Internship::where('college_id', $college->id)
            ->where('drive_type', 'internship_drive')
            ->where('status', 'active')
            ->count();

        // Total applications received on this college's placement drives
        $placementDriveIds = Job::where('college_id', $college->id)
            ->where('drive_type', 'placement_drive')
            ->pluck('id');
        $totalApplications = \App\Models\JobApplication::whereIn('job_id', $placementDriveIds)->count();

        // Placed (hired) students count
        $placedStudents = \App\Models\JobApplication::whereIn('job_id', $placementDriveIds)
            ->where('status', 'hired')
            ->distinct('user_id')
            ->count('user_id');

        // Recent placement drives
        $recentDrives = Job::where('college_id', $college->id)
            ->where('drive_type', 'placement_drive')
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'job_type', 'application_deadline', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_students'          => $totalStudents,
                    'active_placement_drives' => $activePlacementDrives,
                    'active_internship_drives' => $activeInternshipDrives,
                    'total_applications'      => $totalApplications,
                    'placed_students'         => $placedStudents,
                ],
                'recent_drives' => $recentDrives,
                'alerts' => [],
            ]
        ]);
    }

    /**
     * Get all students for the college
     */
    public function students(Request $request)
    {
        $studentsQuery = \App\Models\User::role('student')
            ->whereHas('education', function($q) use ($request) {
                $q->where('college_id', $request->user()->id);
            });
            
        $totalStudents = $studentsQuery->count();
        $students = $studentsQuery->with(['education', 'skills'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $students,
                'stats' => [
                    'total_students' => $totalStudents,
                    'placed' => 0,
                    'in_process' => 0,
                    'unplaced' => $totalStudents
                ]
            ]
        ]);
    }

    public function exportStudents(Request $request)
    {
        $students = \App\Models\User::role('student')
            ->whereHas('education', function($q) use ($request) {
                $q->where('college_id', $request->user()->id);
            })->with('education')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=students.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($students) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Course', 'CGPA']);

            foreach ($students as $student) {
                $edu = $student->education->first();
                fputcsv($file, [
                    $student->id,
                    $student->name,
                    $student->email,
                    $edu ? $edu->course_name : '',
                    $edu ? $edu->score : ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = true;
        while ($csvLine = fgetcsv($handle, 1000, ",")) {
            if ($header) {
                $header = false;
                continue;
            }
            
            // Name, Email, Course
            if (isset($csvLine[0]) && isset($csvLine[1])) {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $csvLine[1]],
                    [
                        'name' => $csvLine[0],
                        'password' => bcrypt('password'), // Default password
                    ]
                );
                
                if (!$user->hasRole('student')) {
                    $user->assignRole('student');
                }
                
                \App\Models\StudentEducation::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'college_id' => $request->user()->id,
                        'course_name' => $csvLine[2] ?? 'B.Tech',
                        'score' => $csvLine[3] ?? '0.0'
                    ]
                );
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Students imported successfully']);
    }

    public function exportReports(Request $request)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=placement_reports.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Report Type', 'Value']);
            fputcsv($file, ['Total Placements', '120']);
            fputcsv($file, ['Highest Package', '24 LPA']);
            fputcsv($file, ['Average Package', '8 LPA']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
