<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use App\Models\IssuedCertificate;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentQuizController extends Controller
{
    /**
     * Get a quiz for a specific lesson (students must be enrolled)
     */
    public function show(Request $request, $lesson_id)
    {
        $lesson = Lesson::with(['quiz.questions.answers'])->findOrFail($lesson_id);
        $quiz = $lesson->quiz;

        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'No quiz for this lesson'], 404);
        }

        // Verify enrollment
        $module = Module::find($lesson->module_id);
        if ($module) {
            $isEnrolled = CourseEnrollment::where('user_id', $request->user()->id)
                ->where('course_id', $module->course_id)
                ->where('status', 'active')
                ->exists();
            if (!$isEnrolled) {
                return response()->json(['success' => false, 'message' => 'You must enroll to access this quiz'], 403);
            }
        }

        // Return questions WITHOUT revealing is_correct
        $formattedQuestions = $quiz->questions->map(fn($q) => [
            'id'       => $q->id,
            'question' => $q->question,
            'type'     => $q->type,
            'options'  => $q->answers->map(fn($a) => [
                'id'   => $a->id,
                'text' => $a->answer_text,
            ]),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'quiz_id'       => $quiz->id,
                'passing_score' => $quiz->passing_score,
                'questions'     => $formattedQuestions,
            ]
        ]);
    }

    /**
     * Submit quiz answers and calculate score
     */
    public function submit(Request $request, $quiz_id)
    {
        $request->validate([
            'answers'                    => 'required|array',
            'answers.*.question_id'      => 'required|integer',
            'answers.*.answer_id'        => 'required|integer',
        ]);

        $quiz = Quiz::with('questions.answers')->findOrFail($quiz_id);
        $user = $request->user();

        $correct = 0;
        $total   = $quiz->questions->count();

        foreach ($quiz->questions as $question) {
            $submittedAnswerId = collect($request->answers)
                ->firstWhere('question_id', $question->id)['answer_id'] ?? null;

            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            if ($submittedAnswerId && $correctAnswer && (int)$submittedAnswerId === (int)$correctAnswer->id) {
                $correct++;
            }
        }

        $score  = $total > 0 ? round(($correct / $total) * 100) : 0;
        $passed = $score >= ($quiz->passing_score ?? 70);

        // Save quiz attempt
        DB::table('quiz_attempts')->insert([
            'quiz_id'    => $quiz_id,
            'user_id'    => $user->id,
            'score'      => $score,
            'passed'     => $passed,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // If passed, mark the associated lesson as complete
        if ($passed) {
            $lesson = Lesson::where('module_id', function ($q) use ($quiz) {
                $q->select('module_id')->from('lessons')
                    ->where('id', $quiz->lesson_id)->limit(1);
            })->first() ?? Lesson::find($quiz->lesson_id);

            if ($lesson) {
                LessonProgress::updateOrCreate(
                    ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                    ['is_completed' => true]
                );

                // Check course completion
                $module = Module::find($lesson->module_id);
                if ($module) {
                    $course = Course::with('modules.lessons')->find($module->course_id);
                    if ($course) {
                        $totalLessons = 0;
                        $completedCount = 0;
                        foreach ($course->modules as $mod) {
                            foreach ($mod->lessons as $les) {
                                $totalLessons++;
                                if (LessonProgress::where('user_id', $user->id)->where('lesson_id', $les->id)->where('is_completed', true)->exists()) {
                                    $completedCount++;
                                }
                            }
                        }
                        if ($totalLessons > 0 && $completedCount >= $totalLessons) {
                            IssuedCertificate::firstOrCreate(
                                ['user_id' => $user->id, 'course_id' => $course->id],
                                [
                                    'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                                    'issued_at'          => now(),
                                    'status'             => 'Issued',
                                ]
                            );
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'score'         => $score,
                'correct'       => $correct,
                'total'         => $total,
                'passed'        => $passed,
                'passing_score' => $quiz->passing_score ?? 70,
            ]
        ]);
    }
}

