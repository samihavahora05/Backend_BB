<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['course_id' => 'required|exists:courses,id']);
        $modules = Module::where('course_id', $request->course_id)
            ->orderBy('order')
            ->get();
        return response()->json($modules);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
        ]);

        $module = Module::create($request->all());
        return response()->json($module, 201);
    }

    public function show($id)
    {
        return response()->json(Module::with('lessons')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $module->update($request->all());
        return response()->json($module);
    }

    public function destroy($id)
    {
        Module::findOrFail($id)->delete();
        return response()->json(['message' => 'Module deleted']);
    }
}
