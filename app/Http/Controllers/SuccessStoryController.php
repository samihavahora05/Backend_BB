<?php

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use Illuminate\Http\Request;

class SuccessStoryController extends Controller
{
    /**
     * Public list of featured success stories
     */
    public function index()
    {
        return response()->json(SuccessStory::where('is_featured', true)->latest()->get());
    }

    /**
     * Admin create success story
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'package' => 'nullable|string|max:255',
            'story' => 'required|string',
            'photo_url' => 'nullable|url',
            'is_featured' => 'boolean'
        ]);

        $story = SuccessStory::create($validated);

        return response()->json($story, 201);
    }

    /**
     * Admin show success story
     */
    public function show($id)
    {
        return response()->json(SuccessStory::findOrFail($id));
    }

    /**
     * Admin update success story
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'student_name' => 'sometimes|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'package' => 'nullable|string|max:255',
            'story' => 'sometimes|string',
            'photo_url' => 'nullable|url',
            'is_featured' => 'sometimes|boolean'
        ]);

        $story = SuccessStory::findOrFail($id);
        $story->update($validated);

        return response()->json($story);
    }

    /**
     * Admin delete success story
     */
    public function destroy($id)
    {
        $story = SuccessStory::findOrFail($id);
        $story->delete();

        return response()->json(['message' => 'Success story deleted successfully']);
    }
}
