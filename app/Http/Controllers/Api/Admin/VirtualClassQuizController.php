<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\VirtualClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VirtualClassQuizController extends Controller
{
    public function show(int $virtualClassId)
    {
        $class = VirtualClass::findOrFail($virtualClassId);
        return response()->json([
            'success' => true,
            'data' => $class->quiz()->with('questions.answers')->first(),
        ]);
    }

    public function upsert(Request $request, int $virtualClassId)
    {
        VirtualClass::findOrFail($virtualClassId);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'passing_score' => 'required|integer|min:1|max:100',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:single,multiple,true_false',
            'questions.*.answers' => 'required|array|min:2',
            'questions.*.answers.*.text' => 'required|string',
            'questions.*.answers.*.is_correct' => 'required|boolean',
        ]);

        $quiz = DB::transaction(function () use ($data, $virtualClassId) {
            $quiz = Quiz::updateOrCreate(
                ['virtual_class_id' => $virtualClassId],
                [
                    'lesson_id' => null,
                    'title' => $data['title'] ?? 'Virtual Class MCQ',
                    'questions' => [],
                    'passing_score' => $data['passing_score'],
                ]
            );

            $quiz->questions()->each(fn ($question) => $question->answers()->delete());
            $quiz->questions()->delete();

            foreach ($data['questions'] as $order => $questionData) {
                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'order' => $order,
                ]);

                foreach ($questionData['answers'] as $answerData) {
                    QuizAnswer::create([
                        'question_id' => $question->id,
                        'answer_text' => $answerData['text'],
                        'is_correct' => $answerData['is_correct'],
                    ]);
                }
            }

            return $quiz->load('questions.answers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Virtual class MCQ saved successfully.',
            'data' => $quiz,
        ]);
    }

    public function destroy(int $virtualClassId)
    {
        $quiz = Quiz::where('virtual_class_id', $virtualClassId)->first();
        if ($quiz) {
            $quiz->questions()->each(fn ($question) => $question->answers()->delete());
            $quiz->questions()->delete();
            $quiz->delete();
        }

        return response()->json(['success' => true, 'message' => 'Virtual class MCQ removed.']);
    }
}
