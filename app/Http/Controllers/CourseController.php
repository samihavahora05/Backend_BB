<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;

class CourseController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = Course::with(['category', 'expert'])
            ->when($request->category_id, fn($q, $id) => $q->where('category_id', $id))
            ->where('is_published', true);

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'price', 'created_at'],
            ['title', 'description']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric',
        ]);

        $course = Course::create([
            'category_id' => $request->category_id,
            'expert_id' => $request->user()->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'description' => $request->description,
            'price' => $request->price,
            'level' => $request->level,
            'is_published' => true, // default to published for easier testing
        ]);

        // Send notifications to all students/users for new course
        $students = User::role('student')->get();
        foreach ($students as $student) {
            $student->notify(new PlatformNotification(
                "New Course Available! 🎓",
                "Explore our new course: '{$course->title}' by " . $request->user()->name,
                'new_course',
                ['course_id' => $course->id]
            ));
        }

        return response()->json($course, 201);
    }


    public function show($id)
    {
        $course = Course::with(['category', 'expert', 'modules.lessons'])->findOrFail($id);
        return response()->json($course);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        Gate::authorize('update', $course);
        
        if ($request->has('title')) {
            $course->slug = Str::slug($request->title) . '-' . uniqid();
        }

        $course->update($request->all());
        return response()->json($course);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        Gate::authorize('delete', $course);
        
        $course->delete();
        return response()->json(['message' => 'Course deleted']);
    }
}
