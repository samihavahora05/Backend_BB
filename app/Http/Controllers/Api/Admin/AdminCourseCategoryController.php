<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use App\Services\CourseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCourseCategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CourseCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $categories = $this->categoryService->getCategories($request->all());
        return response()->json($categories);
    }

    public function active()
    {
        $categories = $this->categoryService->getAllActiveCategories();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:course_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'image' => 'nullable|image|max:2048',
            'position' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $category = $this->categoryService->createCategory($data);
        return response()->json(['message' => 'Category created successfully', 'data' => $category], 201);
    }

    public function show($id)
    {
        $category = CourseCategory::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = CourseCategory::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:course_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'image' => 'nullable|image|max:2048',
            'position' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $category = $this->categoryService->updateCategory($category, $data);
        return response()->json(['message' => 'Category updated successfully', 'data' => $category]);
    }

    public function destroy($id)
    {
        $category = CourseCategory::findOrFail($id);
        $this->categoryService->deleteCategory($category);
        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function restore($id)
    {
        $this->categoryService->restoreCategory($id);
        return response()->json(['message' => 'Category restored successfully']);
    }

    public function forceDelete($id)
    {
        $this->categoryService->forceDeleteCategory($id);
        return response()->json(['message' => 'Category permanently deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $this->categoryService->bulkDelete($request->ids);
        return response()->json(['message' => 'Categories deleted successfully']);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);
        $this->categoryService->bulkStatus($request->ids, $request->status);
        return response()->json(['message' => 'Status updated successfully']);
    }

    public function export(Request $request)
    {
        $categories = $this->categoryService->getCategories(array_merge($request->all(), ['per_page' => 'all']));
        
        $csvHeader = "ID,Name,Slug,Parent ID,Status,Created At\n";
        $csvData = "";
        
        foreach ($categories as $cat) {
            $csvData .= "{$cat->id},\"{$cat->name}\",\"{$cat->slug}\",\"{$cat->parent_id}\",\"{$cat->status}\",\"{$cat->created_at}\"\n";
        }
        
        return response($csvHeader . $csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="course_categories.csv"');
    }
}
