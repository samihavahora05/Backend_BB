<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseCategoryController extends Controller
{
    public function index()
    {
        return response()->json(CourseCategory::where('status', 'active')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string'
        ]);

        $category = CourseCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon
        ]);

        return response()->json($category, 201);
    }

    public function show(CourseCategory $courseCategory)
    {
        return response()->json($courseCategory);
    }

    public function update(Request $request, CourseCategory $courseCategory)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($request->has('name')) {
            $courseCategory->slug = Str::slug($request->name);
        }

        $courseCategory->update($request->all());

        return response()->json($courseCategory);
    }

    public function destroy(CourseCategory $courseCategory)
    {
        $courseCategory->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
