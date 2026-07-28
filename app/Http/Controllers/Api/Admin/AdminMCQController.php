<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMCQController extends Controller
{
    /**
     * List all quiz/MCQ results from student_progress table
     */
    public function results(Request $request)
    {
        $query = StudentProgress::with(['user', 'course'])
            ->whereNotNull('average_quiz_score');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $results = $query->latest()
            ->paginate($request->input('per_page', 20));

        $items = $results->map(function ($r) {
            $score     = round($r->average_quiz_score ?? 0);
            $total     = 100;
            $passed    = $score >= 50;
            return [
                'id'          => $r->id,
                'studentName' => trim(($r->user->first_name ?? '') . ' ' . ($r->user->last_name ?? '')),
                'email'       => $r->user->email ?? '',
                'quizTitle'   => $r->course->title ?? 'Unknown Course',
                'course_id'   => $r->course_id,
                'score'       => $score,
                'total'       => $total,
                'percentage'  => $score,
                'status'      => $passed ? 'Passed' : 'Failed',
                'timeTaken'   => round(($r->learning_hours ?? 0) * 60) . 'm',
                'date'        => $r->updated_at ? $r->updated_at->format('Y-m-d') : null,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
                'per_page'     => $results->perPage(),
            ],
        ]);
    }

    /**
     * Stats for Statistics tab
     */
    public function stats()
    {
        $total   = StudentProgress::whereNotNull('average_quiz_score')->count();
        $avgScore = StudentProgress::whereNotNull('average_quiz_score')->avg('average_quiz_score') ?? 0;
        $passed  = StudentProgress::whereNotNull('average_quiz_score')
                      ->where('average_quiz_score', '>=', 50)->count();
        $passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $avgTime  = StudentProgress::whereNotNull('learning_hours')->avg('learning_hours') ?? 0;

        return response()->json([
            'data' => [
                'total_attempts'   => $total,
                'average_score'    => round($avgScore, 1),
                'pass_rate'        => $passRate,
                'avg_hours'        => round($avgTime, 1),
            ]
        ]);
    }

    /**
     * Leaderboard – top students by average_quiz_score
     */
    public function leaderboard()
    {
        $top = StudentProgress::with('user')
            ->select('user_id',
                DB::raw('ROUND(AVG(average_quiz_score),1) as avg_score'),
                DB::raw('COUNT(*) as quiz_count')
            )
            ->whereNotNull('average_quiz_score')
            ->groupBy('user_id')
            ->orderByDesc('avg_score')
            ->take(10)
            ->get()
            ->map(function ($r, $i) {
                return [
                    'rank'      => $i + 1,
                    'name'      => trim(($r->user->first_name ?? '') . ' ' . ($r->user->last_name ?? '')),
                    'avgScore'  => $r->avg_score . '%',
                    'quizzes'   => $r->quiz_count,
                ];
            });

        return response()->json(['data' => $top]);
    }

    /**
     * List courses that have at least one quiz result (for the filter dropdown)
     */
    public function courses()
    {
        $courses = Course::whereHas('studentProgress', function ($q) {
            $q->whereNotNull('average_quiz_score');
        })->select('id', 'title')->get();

        return response()->json(['data' => $courses]);
    }

    /**
     * Export CSV
     */
    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MCQResultsExport,
            'mcq_results.xlsx'
        );
    }
}
