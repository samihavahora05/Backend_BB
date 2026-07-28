<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLevel;
use App\Services\CourseLevelService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCourseLevelController extends Controller
{
    protected $levelService;

    public function __construct(CourseLevelService $levelService)
    {
        $this->levelService = $levelService;
    }

    public function index(Request $request)
    {
        $levels = $this->levelService->getLevels($request->all());
        return response()->json($levels);
    }

    public function active()
    {
        $levels = $this->levelService->getAllActiveLevels();
        return response()->json($levels);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $level = $this->levelService->createLevel($data);
        return response()->json(['message' => 'Level created successfully', 'data' => $level], 201);
    }

    public function show($id)
    {
        $level = CourseLevel::findOrFail($id);
        return response()->json($level);
    }

    public function update(Request $request, $id)
    {
        $level = CourseLevel::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $level = $this->levelService->updateLevel($level, $data);
        return response()->json(['message' => 'Level updated successfully', 'data' => $level]);
    }

    public function destroy($id)
    {
        $level = CourseLevel::findOrFail($id);
        $this->levelService->deleteLevel($level);
        return response()->json(['message' => 'Level deleted successfully']);
    }

    public function restore($id)
    {
        $this->levelService->restoreLevel($id);
        return response()->json(['message' => 'Level restored successfully']);
    }

    public function forceDelete($id)
    {
        $this->levelService->forceDeleteLevel($id);
        return response()->json(['message' => 'Level permanently deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $this->levelService->bulkDelete($request->ids);
        return response()->json(['message' => 'Levels deleted successfully']);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);
        $this->levelService->bulkStatus($request->ids, $request->status);
        return response()->json(['message' => 'Status updated successfully']);
    }

    public function export(Request $request)
    {
        $levels = $this->levelService->getLevels(array_merge($request->all(), ['per_page' => 'all']));
        
        $csvHeader = "ID,Title,Slug,Status,Created At\n";
        $csvData = "";
        
        foreach ($levels as $lvl) {
            $csvData .= "{$lvl->id},\"{$lvl->title}\",\"{$lvl->slug}\",\"{$lvl->status}\",\"{$lvl->created_at}\"\n";
        }
        
        return response($csvHeader . $csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="course_levels.csv"');
    }
}
