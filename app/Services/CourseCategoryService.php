<?php

namespace App\Services;

use App\Models\CourseCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CourseCategoryService
{
    public function getCategories($params)
    {
        $query = CourseCategory::with('parent')->orderBy('position', 'asc');

        if (!empty($params['search'])) {
            $query->where('name', 'like', '%' . $params['search'] . '%');
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (isset($params['trashed']) && $params['trashed'] == 'true') {
            $query->onlyTrashed();
        }

        $perPage = $params['per_page'] ?? 10;
        return $perPage === 'all' ? $query->get() : $query->paginate($perPage);
    }

    public function getAllActiveCategories()
    {
        return CourseCategory::where('status', 'active')->orderBy('position', 'asc')->get();
    }

    public function createCategory($data)
    {
        $data['slug'] = Str::slug($data['name']);
        if (CourseCategory::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $data['slug'] . '-' . uniqid();
        }
        $data['created_by'] = auth()->id();

        if (isset($data['icon']) && $data['icon'] instanceof UploadedFile) {
            $data['icon'] = $data['icon']->store('categories', 'public');
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('categories', 'public');
        }

        return CourseCategory::create($data);
    }

    public function updateCategory(CourseCategory $category, $data)
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
            if (CourseCategory::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $data['slug'] . '-' . uniqid();
            }
        }
        
        $data['updated_by'] = auth()->id();

        if (isset($data['icon']) && $data['icon'] instanceof UploadedFile) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $data['icon']->store('categories', 'public');
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('categories', 'public');
        }

        $category->update($data);
        return $category;
    }

    public function deleteCategory(CourseCategory $category)
    {
        return $category->delete();
    }

    public function forceDeleteCategory($id)
    {
        $category = CourseCategory::withTrashed()->findOrFail($id);
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        return $category->forceDelete();
    }

    public function restoreCategory($id)
    {
        $category = CourseCategory::withTrashed()->findOrFail($id);
        return $category->restore();
    }

    public function bulkDelete($ids)
    {
        return CourseCategory::whereIn('id', $ids)->delete();
    }

    public function bulkStatus($ids, $status)
    {
        return CourseCategory::whereIn('id', $ids)->update(['status' => $status, 'updated_by' => auth()->id()]);
    }
}
