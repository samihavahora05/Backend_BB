<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class AdminCourseEnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseEnrollment::with(['user', 'course.instructor']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('course', function ($qc) use ($search) {
                    $qc->where('title', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'All') {
            $query->where('status', strtolower($request->status));
        }

        if ($request->has('course_id') && !empty($request->course_id)) {
            $query->where('course_id', $request->course_id);
        }

        $enrollments = $query->latest()->paginate(15);

        return response()->json($enrollments);
    }

    public function show($id)
    {
        $enrollment = CourseEnrollment::with(['user', 'course.instructor'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $enrollment
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,pending,completed,cancelled,refunded',
            'payment_status' => 'nullable|in:Paid,Pending,Refunded'
        ]);

        $enrollment = CourseEnrollment::findOrFail($id);
        
        $enrollment->status = $request->status;
        $enrollment->save();

        return response()->json([
            'success' => true,
            'data' => $enrollment,
            'message' => 'Status updated successfully'
        ]);
    }

    public function export(Request $request)
    {
        $status = $request->input('status', 'All');
        $courseId = $request->input('course_id', '');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CourseEnrollmentsExport($status, $courseId),
            'enrollments.xlsx'
        );
    }

    public function destroy($id)
    {
        $enrollment = CourseEnrollment::findOrFail($id);
        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment deleted successfully'
        ]);
    }
}
