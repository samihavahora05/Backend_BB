<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminQuizController extends Controller
{
    /**
     * Get all quiz data for a lesson (including questions and answers)
     */
    public function show($lesson_id)
    {
        $lesson = Lesson::with(['quiz.questions.answers'])->findOrFail($lesson_id);

        return response()->json([
            'success' => true,
            'data' => [
                'lesson' => ['id' => $lesson->id, 'title' => $lesson->title],
                'quiz'   => $lesson->quiz,
            ]
        ]);
    }

    /**
     * Create or update the quiz for a lesson (including all questions/answers)
     * Accepts: { passing_score, questions: [{ question, type, answers: [{ text, is_correct }] }] }
     */
    public function upsert(Request $request, $lesson_id)
    {
        $data = $request->validate([
            'passing_score'            => 'required|integer|min:1|max:100',
            'questions'                => 'required|array|min:1',
            'questions.*.question'     => 'required|string',
            'questions.*.type'         => 'required|in:single,multiple,true_false',
            'questions.*.answers'      => 'required|array|min:2',
            'questions.*.answers.*.text'       => 'required|string',
            'questions.*.answers.*.is_correct' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $quiz = Quiz::updateOrCreate(
                ['lesson_id' => $lesson_id],
                ['passing_score' => $data['passing_score']]
            );

            // Wipe and re-create all questions (simplest approach for admin builder)
            $quiz->questions()->each(fn($q) => $q->answers()->delete());
            $quiz->questions()->delete();

            foreach ($data['questions'] as $i => $qData) {
                $question = QuizQuestion::create([
                    'quiz_id'  => $quiz->id,
                    'question' => $qData['question'],
                    'type'     => $qData['type'],
                    'order'    => $i,
                ]);

                foreach ($qData['answers'] as $aData) {
                    QuizAnswer::create([
                        'question_id' => $question->id,
                        'answer_text' => $aData['text'],
                        'is_correct'  => $aData['is_correct'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quiz saved successfully',
                'data'    => $quiz->load('questions.answers'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a quiz and all its questions
     */
    public function destroy($lesson_id)
    {
        $quiz = Quiz::where('lesson_id', $lesson_id)->firstOrFail();
        $quiz->questions()->each(fn($q) => $q->answers()->delete());
        $quiz->questions()->delete();
        $quiz->delete();

        return response()->json(['success' => true, 'message' => 'Quiz deleted']);
    }
}
