<?php

namespace App\Http\Controllers\Api\Intern;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternAssessmentController extends Controller
{
    /**
     * List all available assessments and intern's previous attempts
     * GET /api/intern/assessments
     */
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $assessments = Assessment::where('status', 'active')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($assessment) use ($user) {
                $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
                    ->where('user_id', $user->id)
                    ->orderBy('id', 'desc')
                    ->get();

                $activeAttempt = $attempts->firstWhere('status', 'in_progress');
                $bestAttempt = $attempts->where('status', 'completed')->sortByDesc('score')->first();
                $latestAttempt = $attempts->first();

                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'slug' => $assessment->slug,
                    'description' => $assessment->description,
                    'total_questions' => $assessment->total_questions,
                    'total_marks' => $assessment->total_marks,
                    'passing_percentage' => $assessment->passing_percentage,
                    'duration_minutes' => $assessment->duration_minutes,
                    'category_breakdown' => $assessment->category_breakdown,
                    'has_active_attempt' => !is_null($activeAttempt),
                    'active_attempt_id' => $activeAttempt?->id,
                    'total_attempts' => $attempts->count(),
                    'best_score' => $bestAttempt?->score,
                    'best_percentage' => $bestAttempt?->percentage,
                    'latest_status' => $latestAttempt?->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Get assessment overview details
     * GET /api/intern/assessments/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user() ?? auth()->user();
        $assessment = Assessment::where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        $activeAttempt = $attempts->firstWhere('status', 'in_progress');

        return response()->json([
            'success' => true,
            'data' => [
                'assessment' => $assessment,
                'has_active_attempt' => !is_null($activeAttempt),
                'active_attempt_id' => $activeAttempt?->id,
                'attempts' => $attempts,
            ]
        ]);
    }

    /**
     * Start a new assessment attempt or resume an existing in-progress attempt
     * POST /api/intern/assessments/{id}/start
     */
    public function start(Request $request, $id)
    {
        $user = $request->user() ?? auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $assessment = Assessment::where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        // Check if there is already an in_progress attempt
        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'total_questions' => $assessment->total_questions,
                'unanswered' => $assessment->total_questions,
                'status' => 'in_progress',
            ]);
        }

        // Fetch questions WITHOUT correct_answer to guarantee security
        $questions = AssessmentQuestion::where('assessment_id', $assessment->id)
            ->orderBy('order', 'asc')
            ->get([
                'id',
                'assessment_id',
                'order',
                'category',
                'question',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'marks',
            ]);

        // Fetch already answered questions for this attempt (for seamless page reloads)
        $savedAnswers = AssessmentAnswer::where('attempt_id', $attempt->id)
            ->pluck('selected_answer', 'question_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'started_at' => $attempt->started_at->toISOString(),
                    'total_questions' => $attempt->total_questions,
                    'status' => $attempt->status,
                ],
                'assessment' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'duration_minutes' => $assessment->duration_minutes,
                    'category_breakdown' => $assessment->category_breakdown,
                ],
                'questions' => $questions,
                'saved_answers' => $savedAnswers,
            ]
        ]);
    }

    /**
     * Auto-save or update individual answer during assessment
     * POST /api/intern/assessments/{id}/save-answer
     */
    public function saveAnswer(Request $request, $id)
    {
        $request->validate([
            'attempt_id' => 'required|integer',
            'question_id' => 'required|integer',
            'selected_answer' => 'nullable|in:A,B,C,D,a,b,c,d',
        ]);

        $user = $request->user() ?? auth()->user();
        $attempt = AssessmentAttempt::where('id', $request->attempt_id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $question = AssessmentQuestion::where('id', $request->question_id)
            ->where('assessment_id', $attempt->assessment_id)
            ->firstOrFail();

        $selected = $request->selected_answer ? strtoupper($request->selected_answer) : null;

        if ($selected) {
            AssessmentAnswer::updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_answer' => $selected,
                ]
            );
        } else {
            // Cleared answer
            AssessmentAnswer::where('attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->delete();
        }

        $answeredCount = AssessmentAnswer::where('attempt_id', $attempt->id)
            ->whereNotNull('selected_answer')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'answered_count' => $answeredCount,
                'unanswered_count' => $attempt->total_questions - $answeredCount,
            ]
        ]);
    }

    /**
     * Submit assessment and calculate score server-side
     * POST /api/intern/assessments/{id}/submit
     */
    public function submit(Request $request, $id)
    {
        $request->validate([
            'attempt_id' => 'required|integer',
            'answers' => 'nullable|array', // Optional map of question_id => selected_answer (A/B/C/D)
        ]);

        $user = $request->user() ?? auth()->user();
        $attempt = AssessmentAttempt::where('id', $request->attempt_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attempt->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Assessment has already been submitted.',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage,
                    'correct_answers' => $attempt->correct_answers,
                    'wrong_answers' => $attempt->wrong_answers,
                    'unanswered' => $attempt->unanswered,
                ]
            ]);
        }

        // Fetch all questions for this assessment including correct_answer (server-side ONLY)
        $questions = AssessmentQuestion::where('assessment_id', $attempt->assessment_id)
            ->get(['id', 'category', 'correct_answer', 'marks', 'order']);

        $rawAnswers = $request->input('answers', []);
        $submittedAnswers = [];
        if (is_array($rawAnswers)) {
            foreach ($rawAnswers as $k => $v) {
                if (is_array($v) && isset($v['question_id'])) {
                    $submittedAnswers[$v['question_id']] = $v['selected_answer'] ?? null;
                } else {
                    $submittedAnswers[$k] = $v;
                }
            }
        }

        // Also merge any answers saved in the DB
        $dbAnswers = AssessmentAnswer::where('attempt_id', $attempt->id)->get()->keyBy('question_id');

        $totalCorrect = 0;
        $totalWrong = 0;
        $totalUnanswered = 0;
        $totalScore = 0;

        $categoryStats = [
            'Web Development' => ['total' => 0, 'correct' => 0, 'wrong' => 0, 'unanswered' => 0, 'score' => 0],
            'Digital Marketing' => ['total' => 0, 'correct' => 0, 'wrong' => 0, 'unanswered' => 0, 'score' => 0],
            'Graphic Designing' => ['total' => 0, 'correct' => 0, 'wrong' => 0, 'unanswered' => 0, 'score' => 0],
        ];

        DB::transaction(function () use (
            $attempt, $questions, $submittedAnswers, $dbAnswers,
            &$totalCorrect, &$totalWrong, &$totalUnanswered, &$totalScore, &$categoryStats
        ) {
            foreach ($questions as $q) {
                $category = $q->category ?? 'General';
                if (!isset($categoryStats[$category])) {
                    $categoryStats[$category] = ['total' => 0, 'correct' => 0, 'wrong' => 0, 'unanswered' => 0, 'score' => 0];
                }
                $categoryStats[$category]['total']++;

                // Selected answer from submission or previously saved DB state
                $selected = null;
                if (isset($submittedAnswers[$q->id])) {
                    $selected = strtoupper(trim((string)$submittedAnswers[$q->id]));
                } elseif (isset($dbAnswers[$q->id])) {
                    $selected = $dbAnswers[$q->id]->selected_answer;
                }

                $correct = strtoupper(trim((string)$q->correct_answer));
                $isCorrect = false;
                $marksObtained = 0;

                if (empty($selected) || !in_array($selected, ['A', 'B', 'C', 'D'])) {
                    $selected = null;
                    $totalUnanswered++;
                    $categoryStats[$category]['unanswered']++;
                } elseif ($selected === $correct) {
                    $isCorrect = true;
                    $marksObtained = $q->marks ?? 1;
                    $totalCorrect++;
                    $totalScore += $marksObtained;
                    $categoryStats[$category]['correct']++;
                    $categoryStats[$category]['score'] += $marksObtained;
                } else {
                    $isCorrect = false;
                    $totalWrong++;
                    $categoryStats[$category]['wrong']++;
                }

                // Update or create answer row
                AssessmentAnswer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'selected_answer' => $selected,
                        'correct_answer' => $correct,
                        'is_correct' => $selected ? $isCorrect : null,
                        'marks_obtained' => $marksObtained,
                    ]
                );
            }

            $percentage = $attempt->total_questions > 0 
                ? round(($totalScore / $attempt->total_questions) * 100, 2) 
                : 0.00;

            $attempt->update([
                'submitted_at' => now(),
                'correct_answers' => $totalCorrect,
                'wrong_answers' => $totalWrong,
                'unanswered' => $totalUnanswered,
                'score' => $totalScore,
                'percentage' => $percentage,
                'status' => 'completed',
                'category_scores' => $categoryStats,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Assessment submitted successfully.',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'percentage' => $attempt->percentage,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'wrong_answers' => $attempt->wrong_answers,
                'unanswered' => $attempt->unanswered,
                'category_scores' => $attempt->category_scores,
                'submitted_at' => $attempt->submitted_at->toISOString(),
            ]
        ]);
    }

    /**
     * Get detailed scorecard and question-by-question review of an attempt
     * GET /api/intern/assessments/{id}/result/{attemptId}
     */
    public function getResult(Request $request, $id, $attemptId)
    {
        $user = $request->user() ?? auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Strict isolation: Intern can ONLY view their own attempt
        $attempt = AssessmentAttempt::with(['assessment'])
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

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
                'attempt' => [
                    'id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'assessment_title' => $attempt->assessment->title,
                    'started_at' => $attempt->started_at?->toISOString(),
                    'submitted_at' => $attempt->submitted_at?->toISOString(),
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'wrong_answers' => $attempt->wrong_answers,
                    'unanswered' => $attempt->unanswered,
                    'score' => $attempt->score,
                    'percentage' => (float)$attempt->percentage,
                    'status' => $attempt->status,
                    'category_scores' => $attempt->category_scores,
                ],
                'answers' => $answers,
            ]
        ]);
    }

    /**
     * Get attempt history for the authenticated intern
     * GET /api/intern/assessments/history
     */
    public function history(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $attempts = AssessmentAttempt::with(['assessment:id,title,slug,total_questions,total_marks'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }
}
