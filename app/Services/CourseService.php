<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CourseService
{
    public function getCourses($params)
    {
        $query = Course::with(['category', 'level', 'expert'])->orderBy('created_at', 'desc');

        if (!empty($params['search'])) {
            $query->where('title', 'like', '%' . $params['search'] . '%');
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['level_id'])) {
            $query->where('level_id', $params['level_id']);
        }

        if (!empty($params['status']) && $params['status'] !== 'All') {
            if ($params['status'] === 'Featured') {
                $query->where('is_featured', true);
            } elseif ($params['status'] === 'Archived') {
                $query->where('is_archived', true);
            } else {
                $query->where('status', $params['status'])->where('is_archived', false);
            }
        } else {
            if (!isset($params['status']) || $params['status'] !== 'Archived') {
                $query->where('is_archived', false);
            }
        }

        if (isset($params['trashed']) && $params['trashed'] == 'true') {
            $query->onlyTrashed();
        }

        $perPage = $params['per_page'] ?? 10;
        return $perPage === 'all' ? $query->get() : $query->paginate($perPage);
    }

    public function createCourse($data)
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
            if (Course::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $data['slug'] . '-' . uniqid();
            }
        }

        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            $data['thumbnail'] = $data['thumbnail']->store('courses/thumbnails', 'public');
        }

        return Course::create($data);
    }

    public function updateCourse(Course $course, $data)
    {
        if (isset($data['title']) && $data['title'] !== $course->title) {
            $data['slug'] = Str::slug($data['title']);
            if (Course::where('slug', $data['slug'])->where('id', '!=', $course->id)->exists()) {
                $data['slug'] = $data['slug'] . '-' . uniqid();
            }
        }

        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $data['thumbnail']->store('courses/thumbnails', 'public');
        }

        $course->update($data);
        return $course;
    }

    public function deleteCourse(Course $course)
    {
        return $course->delete();
    }

    public function bulkDelete($ids)
    {
        return Course::whereIn('id', $ids)->delete();
    }

    public function bulkStatus($ids, $status)
    {
        return Course::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function duplicateCourse($id)
    {
        $course = Course::findOrFail($id);
        $newCourse = $course->replicate();
        $newCourse->title = $course->title . ' (Copy)';
        $newCourse->slug = Str::slug($newCourse->title) . '-' . uniqid();
        $newCourse->status = 'Draft';
        $newCourse->is_published = false;
        $newCourse->push();
        return $newCourse;
    }

    public function updateStatus(Course $course, $status)
    {
        $course->update([
            'status' => $status,
            'is_published' => $status === 'Published'
        ]);
        return $course;
    }

    public function toggleArchive(Course $course)
    {
        $course->update(['is_archived' => !$course->is_archived]);
        return $course;
    }
}
