<?php

namespace App\Services;

use App\Models\CourseLevel;
use Illuminate\Support\Str;

class CourseLevelService
{
    public function getLevels($params)
    {
        $query = CourseLevel::orderBy('position', 'asc');

        if (!empty($params['search'])) {
            $query->where('title', 'like', '%' . $params['search'] . '%');
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

    public function getAllActiveLevels()
    {
        return CourseLevel::where('status', 'active')->orderBy('position', 'asc')->get();
    }

    public function createLevel($data)
    {
        $data['slug'] = Str::slug($data['title']);
        if (CourseLevel::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $data['slug'] . '-' . uniqid();
        }
        $data['created_by'] = auth()->id();

        return CourseLevel::create($data);
    }

    public function updateLevel(CourseLevel $level, $data)
    {
        if (isset($data['title']) && $data['title'] !== $level->title) {
            $data['slug'] = Str::slug($data['title']);
            if (CourseLevel::where('slug', $data['slug'])->where('id', '!=', $level->id)->exists()) {
                $data['slug'] = $data['slug'] . '-' . uniqid();
            }
        }
        
        $data['updated_by'] = auth()->id();
        $level->update($data);
        return $level;
    }

    public function deleteLevel(CourseLevel $level)
    {
        return $level->delete();
    }

    public function forceDeleteLevel($id)
    {
        $level = CourseLevel::withTrashed()->findOrFail($id);
        return $level->forceDelete();
    }

    public function restoreLevel($id)
    {
        $level = CourseLevel::withTrashed()->findOrFail($id);
        return $level->restore();
    }

    public function bulkDelete($ids)
    {
        return CourseLevel::whereIn('id', $ids)->delete();
    }

    public function bulkStatus($ids, $status)
    {
        return CourseLevel::whereIn('id', $ids)->update(['status' => $status, 'updated_by' => auth()->id()]);
    }
}
