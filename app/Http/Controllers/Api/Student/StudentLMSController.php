<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\StudentCourseNote;
use App\Models\CourseResource;
use App\Models\CourseQuestion;
use App\Models\CourseAnswer;
use Illuminate\Http\Request;

class StudentLMSController extends Controller
{
    // ─── Verify the student is enrolled ──────────────────────────────
    private function verifyEnrollment($userId, $courseId)
    {
        return CourseEnrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }

    // ─── NOTES ───────────────────────────────────────────────────────

    public function getNotes(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $notes = StudentCourseNote::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->with('lesson:id,title')
            ->latest()
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'timestamp'  => $n->timestamp,
                'text'       => $n->note_text,
                'lessonId'   => $n->lesson_id,
                'lessonTitle'=> $n->lesson?->title ?? 'General',
                'createdAt'  => $n->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'data' => $notes]);
    }

    public function addNote(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $request->validate([
            'note_text'  => 'required|string|max:2000',
            'timestamp'  => 'nullable|string|max:20',
            'lesson_id'  => 'nullable|integer|exists:lessons,id',
        ]);

        $note = StudentCourseNote::create([
            'user_id'    => $user->id,
            'course_id'  => $courseId,
            'lesson_id'  => $request->lesson_id,
            'timestamp'  => $request->timestamp,
            'note_text'  => $request->note_text,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note saved!',
            'data' => [
                'id'        => $note->id,
                'timestamp' => $note->timestamp,
                'text'      => $note->note_text,
                'lessonId'  => $note->lesson_id,
                'createdAt' => 'Just now',
            ]
        ]);
    }

    public function deleteNote(Request $request, $courseId, $noteId)
    {
        $user = $request->user();

        $note = StudentCourseNote::where('id', $noteId)
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note not found'], 404);
        }

        $note->delete();

        return response()->json(['success' => true, 'message' => 'Note deleted']);
    }

    // ─── RESOURCES ───────────────────────────────────────────────────

    public function getResources(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $resources = CourseResource::where('course_id', $courseId)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(fn($r) => [
                'id'       => $r->id,
                'title'    => $r->title,
                'fileType' => $r->file_type,
                'fileSize' => $r->file_size,
                'url'      => $r->file_url ?? ($r->file_path ? asset('storage/' . $r->file_path) : null),
            ]);

        return response()->json(['success' => true, 'data' => $resources]);
    }

    // ─── Q&A ─────────────────────────────────────────────────────────

    public function getQuestions(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $questions = CourseQuestion::with(['student:id,name', 'answers.user:id,name'])
            ->where('course_id', $courseId)
            ->whereNotIn('status', ['Closed'])
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->get()
            ->map(fn($q) => [
                'id'       => $q->id,
                'user'     => $q->student?->name ?? 'Student',
                'avatar'   => 'https://ui-avatars.com/api/?name=' . urlencode($q->student?->name ?? 'Student') . '&background=1B2A6B&color=fff',
                'title'    => $q->title,
                'text'     => $q->question,
                'time'     => $q->created_at->diffForHumans(),
                'isPinned' => $q->is_pinned,
                'status'   => $q->status,
                'answers'  => $q->answers->map(fn($a) => [
                    'id'      => $a->id,
                    'user'    => $a->user?->name ?? 'Instructor',
                    'avatar'  => 'https://ui-avatars.com/api/?name=' . urlencode($a->user?->name ?? 'Instructor') . '&background=C9A227&color=fff',
                    'text'    => $a->answer,
                    'time'    => $a->created_at->diffForHumans(),
                    'isAdmin' => $a->is_admin,
                ]),
            ]);

        return response()->json(['success' => true, 'data' => $questions]);
    }

    public function postQuestion(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $request->validate([
            'title'    => 'nullable|string|max:255',
            'question' => 'required|string|max:5000',
        ]);

        $question = CourseQuestion::create([
            'course_id'  => $courseId,
            'student_id' => $user->id,
            'title'      => $request->title ?? substr($request->question, 0, 80),
            'question'   => $request->question,
            'status'     => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question posted!',
            'data'    => [
                'id'     => $question->id,
                'user'   => $user->name,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=1B2A6B&color=fff',
                'title'  => $question->title,
                'text'   => $question->question,
                'time'   => 'Just now',
                'status' => $question->status,
                'answers'=> [],
            ]
        ]);
    }

    public function postAnswer(Request $request, $courseId, $questionId)
    {
        $user = $request->user();

        if (!$this->verifyEnrollment($user->id, $courseId)) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $request->validate(['answer' => 'required|string|max:5000']);

        $question = CourseQuestion::where('course_id', $courseId)->findOrFail($questionId);

        $answer = CourseAnswer::create([
            'question_id' => $question->id,
            'user_id'     => $user->id,
            'answer'      => $request->answer,
            'is_admin'    => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Answer posted!',
            'data'    => [
                'id'      => $answer->id,
                'user'    => $user->name,
                'avatar'  => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=1B2A6B&color=fff',
                'text'    => $answer->answer,
                'time'    => 'Just now',
                'isAdmin' => false,
            ]
        ]);
    }
}
