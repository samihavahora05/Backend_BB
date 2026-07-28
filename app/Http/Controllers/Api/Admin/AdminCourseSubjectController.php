<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCourseSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $search = $request->query('search', '');

        $query = CourseSubject::query();

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $subjects = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $subjects->items(),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:course_subjects',
        ]);

        $subject = CourseSubject::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'status' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Course subject created successfully',
            'data' => $subject
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subject = CourseSubject::findOrFail($id);
        return response()->json($subject);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subject = CourseSubject::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255|unique:course_subjects,title,' . $subject->id,
        ]);

        $subject->update([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Course subject updated successfully',
            'data' => $subject
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subject = CourseSubject::findOrFail($id);
        $subject->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Course subject deleted successfully'
        ]);
    }

    /**
     * Update subject status
     */
    public function updateStatus(Request $request, $id)
    {
        $subject = CourseSubject::findOrFail($id);
        
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $subject->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subject status updated successfully',
            'data' => $subject
        ]);
    }
}
