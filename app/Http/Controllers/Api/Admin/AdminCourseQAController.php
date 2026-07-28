<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseQuestion;
use App\Models\CourseAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCourseQAController extends Controller
{
    public function stats()
    {
        $total = CourseQuestion::count();
        $pending = CourseQuestion::where('status', 'Pending')->count();
        $answered = CourseQuestion::where('status', 'Answered')->count();
        $resolved = CourseQuestion::where('status', 'Resolved')->count();
        $reported = CourseQuestion::where('is_reported', true)->count();
        $active = CourseQuestion::whereNotIn('status', ['Closed', 'Resolved'])->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'answered' => $answered,
                'resolved' => $resolved,
                'reported' => $reported,
                'active' => $active,
            ]
        ]);
    }

    public function index(Request $request)
    {
        $query = CourseQuestion::with(['student', 'course'])
            ->withCount('answers');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('question', 'like', "%{$search}%")
                  ->orWhereHas('course', function($c) use ($search) {
                      $c->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('student', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'All') {
            if ($request->status === 'Reported') {
                $query->where('is_reported', true);
            } else if ($request->status === 'Pinned') {
                $query->where('is_pinned', true);
            } else {
                $query->where('status', $request->status);
            }
        }

        $perPage = $request->input('per_page', 10);
        $questions = $query->orderBy('is_pinned', 'desc')->latest()->paginate($perPage);

        return response()->json($questions);
    }

    public function show($id)
    {
        $question = CourseQuestion::with(['student', 'course', 'answers.user'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $question
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['content' => 'required|string']);

        $question = CourseQuestion::findOrFail($id);

        $answer = CourseAnswer::create([
            'question_id' => $question->id,
            'user_id' => Auth::id() ?? 1,
            'answer' => $request->content,
            'is_admin' => true,
        ]);

        if ($question->status === 'Pending') {
            $question->update(['status' => 'Answered']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reply posted successfully',
            'data' => $answer->load('user')
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Pending,Answered,Resolved,Closed']);
        
        $question = CourseQuestion::findOrFail($id);
        $updateData = ['status' => $request->status];
        
        if ($request->status === 'Resolved') {
            $updateData['resolved_at'] = now();
        } elseif ($request->status === 'Closed') {
            $updateData['closed_at'] = now();
        }

        $question->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated to ' . $request->status,
            'data' => $question
        ]);
    }

    public function togglePin($id)
    {
        $question = CourseQuestion::findOrFail($id);
        $question->update(['is_pinned' => !$question->is_pinned]);

        return response()->json([
            'status' => 'success',
            'message' => $question->is_pinned ? 'Question pinned' : 'Question unpinned',
            'data' => $question
        ]);
    }

    public function markSpam($id)
    {
        $question = CourseQuestion::findOrFail($id);
        $question->update([
            'is_reported' => true,
            'status' => 'Closed',
            'closed_at' => now(),
            'reported_reason' => 'Marked as spam by Admin'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Marked as spam and closed'
        ]);
    }

    public function destroy($id)
    {
        CourseQuestion::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Question deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        CourseQuestion::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Questions deleted']);
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $export = new \App\Exports\CourseQAExport();
        
        if ($format === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download($export, 'course_qa.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        
        if ($format === 'pdf') {
            $qas = CourseQuestion::with(['student', 'course'])->withCount('answers')->get();
            $html = '<html><head><title>Course Q&A Export</title><style>body { font-family: sans-serif; } table {width:100%; border-collapse: collapse; margin-top: 20px;} th, td {border:1px solid #ddd; padding:8px; text-align:left; font-size: 12px;} th {background:#f4f4f4;} @media print { button { display: none; } }</style></head><body onload="window.print()">';
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center;"><h2>Course Q&A Export</h2><button onclick="window.print()" style="padding: 8px 16px; background: #1B2A6B; color: white; border: none; border-radius: 4px; cursor: pointer;">Print to PDF</button></div>';
            $html .= '<table><tr><th>ID</th><th>Course</th><th>Student</th><th>Title</th><th>Status</th><th>Answers</th></tr>';
            foreach($qas as $qa) {
                $courseTitle = $qa->course ? $qa->course->title : 'Unknown';
                $studentName = $qa->student ? $qa->student->name : 'Unknown';
                $html .= "<tr><td>{$qa->id}</td><td>{$courseTitle}</td><td>{$studentName}</td><td>{$qa->title}</td><td>{$qa->status}</td><td>{$qa->answers_count}</td></tr>";
            }
            $html .= '</table></body></html>';
            return response($html)->header('Content-Type', 'text/html');
        }

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'course_qa.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
