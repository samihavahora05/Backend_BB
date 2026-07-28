<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['module_id' => 'required|exists:modules,id']);
        $lessons = Lesson::where('module_id', $request->module_id)
            ->orderBy('order')
            ->get();
        return response()->json($lessons);
    }

    public function store(Request $request)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'video_url' => 'nullable|url'
        ]);

        $lesson = Lesson::create($request->all());
        return response()->json($lesson, 201);
    }

    public function show($id)
    {
        return response()->json(Lesson::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->update($request->all());
        return response()->json($lesson);
    }

    public function destroy($id)
    {
        Lesson::findOrFail($id)->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }
}
