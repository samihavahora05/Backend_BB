<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAnswer;
use Illuminate\Http\Request;

class AdminAssessmentController extends Controller
{
    /**
     * List all intern assessment attempts for Admin monitoring
     * GET /api/admin/assessments/results
     */
    public function results(Request $request)
    {
        $query = AssessmentAttempt::with([
            'user:id,first_name,last_name,email,phone',
            'assessment:id,title,total_questions,total_marks,passing_percentage',
        ]);

        if ($request->filled('assessment_id')) {
            $query->where('assessment_id', $request->assessment_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attempts = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }

    /**
     * View detailed question-by-question breakdown of any intern attempt
     * GET /api/admin/assessments/results/{attemptId}
     */
    public function attemptDetail(Request $request, $attemptId)
    {
        $attempt = AssessmentAttempt::with([
            'user:id,first_name,last_name,email,phone',
            'assessment',
        ])->findOrFail($attemptId);

        $answers = AssessmentAnswer::with(['question'])
            ->where('attempt_id', $attempt->id)
            ->get()
            ->sortBy(fn($a) => $a->question->order ?? $a->question_id)
            ->values()
            ->map(function ($ans) {
                $q = $ans->question;
                return [
                    'question_id' => $q->id,
                    'order' => $q->order,
                    'category' => $q->category,
                    'question' => $q->question,
                    'option_a' => $q->option_a,
                    'option_b' => $q->option_b,
                    'option_c' => $q->option_c,
                    'option_d' => $q->option_d,
                    'selected_answer' => $ans->selected_answer,
                    'correct_answer' => $ans->correct_answer,
                    'is_correct' => $ans->is_correct,
                    'marks_obtained' => $ans->marks_obtained,
                    'explanation' => $q->explanation,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attempt,
                'answers' => $answers,
            ]
        ]);
    }
}
