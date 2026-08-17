<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\VirtualClass;
use App\Models\VirtualClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentVirtualClassController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $classes = VirtualClass::query()
            ->with(['instructor:id,first_name,last_name,email', 'course:id,title', 'category:id,name', 'quiz:id,virtual_class_id,title,passing_score'])
            ->whereIn('status', ['scheduled', 'live', 'completed'])
            ->latest('start_datetime')
            ->paginate((int) $request->get('per_page', 15));

        $classes->getCollection()->transform(function (VirtualClass $class) use ($userId) {
            $enrollment = $class->enrollments()->where('user_id', $userId)->first();
            return $this->studentClassPayload($class, $enrollment);
        });

        return response()->json([
            'success' => true,
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $class = VirtualClass::with([
            'instructor:id,first_name,last_name,email',
            'course:id,title',
            'category:id,name',
            'quiz:id,virtual_class_id,title,passing_score',
        ])->findOrFail($id);

        if ($class->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'This virtual class is no longer available.'], 404);
        }

        $enrollment = $class->enrollments()->where('user_id', $request->user()->id)->first();

        return response()->json([
            'success' => true,
            'data' => $this->studentClassPayload($class, $enrollment, true),
        ]);
    }

    public function enroll(Request $request, int $id)
    {
        $class = VirtualClass::findOrFail($id);

        if ($class->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'This virtual class is cancelled.'], 422);
        }

        $existing = VirtualClassEnrollment::where('virtual_class_id', $class->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['success' => true, 'message' => 'You are already enrolled.', 'data' => $existing]);
        }

        if (!$class->is_free) {
            return response()->json(['success' => false, 'message' => 'This virtual class requires payment before enrollment.'], 402);
        }

        if ($class->enrolled_count >= $class->max_students) {
            return response()->json(['success' => false, 'message' => 'This virtual class is full.'], 422);
        }

        $enrollment = DB::transaction(function () use ($class, $request) {
            $enrollment = VirtualClassEnrollment::create([
                'virtual_class_id' => $class->id,
                'user_id' => $request->user()->id,
                'status' => 'enrolled',
            ]);

            $class->increment('enrolled_count');
            return $enrollment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Enrolled in virtual class successfully.',
            'data' => $enrollment->load('virtualClass'),
        ], 201);
    }

    public function showQuiz(Request $request, int $id)
    {
        $class = VirtualClass::findOrFail($id);
        $this->ensureQuizAccess($class, $request->user()->id);

        $quiz = $class->quiz()->with('questions.answers')->first();
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'No MCQ is configured for this virtual class.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'quiz_id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score ?? 70,
                'questions' => $quiz->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'type' => $question->type,
                    'options' => $question->answers->map(fn ($answer) => [
                        'id' => $answer->id,
                        'text' => $answer->answer_text,
                    ]),
                ]),
            ],
        ]);
    }

    public function submitQuiz(Request $request, int $id)
    {
        $class = VirtualClass::findOrFail($id);
        $this->ensureQuizAccess($class, $request->user()->id);

        $quiz = $class->quiz()->with('questions.answers')->firstOrFail();

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer_id' => 'required|integer',
        ]);

        $correct = 0;
        $total = $quiz->questions->count();
        $submitted = collect($request->input('answers'));

        foreach ($quiz->questions as $question) {
            $answerId = optional($submitted->firstWhere('question_id', $question->id))['answer_id'] ?? null;
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            if ($answerId && $correctAnswer && (int) $answerId === (int) $correctAnswer->id) {
                $correct++;
            }
        }

        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
        $passingScore = (int) ($quiz->passing_score ?? 70);
        $passed = $score >= $passingScore;

        DB::table('quiz_attempts')->insert([
            'quiz_id' => $quiz->id,
            'user_id' => $request->user()->id,
            'score' => $score,
            'passed' => $passed,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'score' => $score,
                'correct' => $correct,
                'total' => $total,
                'passed' => $passed,
                'passing_score' => $passingScore,
            ],
        ]);
    }

    public function mcqResults(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $attempts = DB::table('quiz_attempts')
                ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                ->leftJoin('virtual_classes', 'quizzes.virtual_class_id', '=', 'virtual_classes.id')
                ->where('quiz_attempts.user_id', $userId)
                ->select(
                    'quiz_attempts.id',
                    'quizzes.title as quiz_title',
                    'virtual_classes.title as course_name',
                    'quiz_attempts.score',
                    'quiz_attempts.passed as is_passed',
                    'quiz_attempts.created_at'
                )
                ->orderByDesc('quiz_attempts.created_at')
                ->get();
        } catch (\Exception $e) {
            $attempts = collect([]);
        }

        return response()->json([
            'success' => true,
            'data' => $attempts
        ]);
    }

    private function ensureQuizAccess(VirtualClass $class, int $userId): void
    {
        if ($class->status === 'cancelled') {
            abort(404, 'Virtual class is unavailable.');
        }

        $enrolled = $class->enrollments()->where('user_id', $userId)->whereIn('status', ['enrolled', 'attended'])->exists();
        if (!$enrolled) {
            abort(403, 'You must enroll in this virtual class before accessing its MCQ.');
        }
    }

    private function studentClassPayload(VirtualClass $class, ?VirtualClassEnrollment $enrollment, bool $details = false): array
    {
        $instructor = $class->instructor;

        $payload = [
            'id' => $class->id,
            'title' => $class->title,
            'description' => $class->description,
            'course' => $class->course,
            'category' => $class->category,
            'instructor' => $instructor ? [
                'id' => $instructor->id,
                'name' => trim($instructor->first_name . ' ' . $instructor->last_name),
                'email' => $instructor->email,
            ] : null,
            'language' => $class->language,
            'duration_minutes' => $class->duration_minutes,
            'start_datetime' => $class->start_datetime,
            'end_datetime' => $class->end_datetime,
            'status' => $class->status,
            'platform' => $class->platform,
            'join_url' => $class->join_url,
            'recording_url' => $class->recording_url,
            'is_recorded' => $class->is_recorded,
            'is_free' => $class->is_free,
            'price' => $class->price,
            'thumbnail' => $class->thumbnail,
            'is_enrolled' => (bool) $enrollment,
            'enrollment_status' => $enrollment?->status,
            'has_mcq' => (bool) $class->quiz,
        ];

        if ($details) {
            $payload['max_students'] = $class->max_students;
            $payload['enrolled_count'] = $class->enrolled_count;
            $payload['mcq'] = $class->quiz ? [
                'id' => $class->quiz->id,
                'title' => $class->quiz->title,
                'passing_score' => $class->quiz->passing_score ?? 70,
            ] : null;
        }

        return $payload;
    }
}
