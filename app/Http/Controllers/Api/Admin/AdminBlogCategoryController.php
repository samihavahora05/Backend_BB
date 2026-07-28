<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBlogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogCategory::withCount('blogs');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->latest()->get(); // For categories, we might want all without pagination or paginated

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = new BlogCategory();
        $category->name = $request->name;
        $category->slug = $request->slug ?? Str::slug($request->name);
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->status = $request->status ?? 'active';
        $category->meta_title = $request->meta_title;
        $category->meta_description = $request->meta_description;

        if ($request->hasFile('image')) {
            $category->image = $request->file('image')->store('blogs/categories', 'public');
        }

        $category->save();

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function update(Request $request, $id)
    {
        $category = BlogCategory::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->name = $request->name;
        if ($request->filled('slug')) $category->slug = $request->slug;
        $category->description = $request->description;
        if ($request->has('parent_id')) $category->parent_id = $request->parent_id;
        if ($request->has('status')) $category->status = $request->status;
        if ($request->has('meta_title')) $category->meta_title = $request->meta_title;
        if ($request->has('meta_description')) $category->meta_description = $request->meta_description;

        if ($request->hasFile('image')) {
            if ($category->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
            $category->image = $request->file('image')->store('blogs/categories', 'public');
        }

        $category->save();

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy($id)
    {
        $category = BlogCategory::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true]);
    }
}
