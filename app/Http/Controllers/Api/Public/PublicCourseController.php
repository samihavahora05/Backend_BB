<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Module;
use Illuminate\Http\Request;

class PublicCourseController extends Controller
{
    /**
     * Public course listing with search, filters, categories, pagination
     * GET /api/public/courses
     */
    public function index(Request $request)
    {
        $query = Course::with(['category', 'level'])->withCount('enrollments')
            ->where('status', 'Published')
            ->where('is_archived', false);

        // Search
        if ($s = $request->query('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('short_description', 'like', "%{$s}%");
            });
        }

        // Category filter
        if ($cat = $request->query('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $cat));
        }

        // Level filter
        if ($level = $request->query('level')) {
            $query->whereHas('level', fn($q) => $q->where('slug', $level));
        }

        // Type filter (Free / Paid)
        if ($type = $request->query('type')) {
            $query->where('course_type', ucfirst($type));
        }

        // Price range
        if ($request->query('min_price') !== null) {
            $query->where('price', '>=', $request->query('min_price'));
        }
        if ($request->query('max_price') !== null) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        // Featured only
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Sort
        $sortMap = [
            'newest'    => ['created_at', 'desc'],
            'oldest'    => ['created_at', 'asc'],
            'price_low' => ['price', 'asc'],
            'price_high'=> ['price', 'desc'],
        ];
        [$sortCol, $sortDir] = $sortMap[$request->query('sort', 'newest')] ?? ['created_at', 'desc'];
        $query->orderBy($sortCol, $sortDir);

        $perPage = min((int)($request->query('per_page', 12)), 50);
        $courses = $query->paginate($perPage);

        $data = $courses->through(fn($c) => [
            'id'                => $c->id,
            'slug'              => $c->slug,
            'title'             => $c->title,
            'short_description' => $c->short_description,
            'thumbnail'         => $c->thumbnail ? asset('storage/' . $c->thumbnail) : null,
            'price'             => $c->price,
            'discount_price'    => $c->discount_price,
            'course_type'       => $c->course_type,
            'language'          => $c->language,
            'duration'          => $c->duration,
            'is_featured'       => $c->is_featured,
            'category'          => ['id' => $c->category?->id, 'name' => $c->category?->name, 'slug' => $c->category?->slug],
            'level'             => ['id' => $c->level?->id, 'name' => $c->level?->name],
            'enrolled_count'    => $c->enrollments_count ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Public course detail page
     * GET /api/public/courses/{slug}
     */
    public function show($slug)
    {
        $course = Course::with([
            'category', 'level', 'expert',
            'modules' => fn($q) => $q->orderBy('order'),
            'modules.lessons' => fn($q) => $q->orderBy('order')->select('id', 'module_id', 'title', 'type', 'duration_minutes'),
        ])
        ->where('slug', $slug)
        ->where('status', 'Published')
        ->firstOrFail();

        $totalLessons = $course->modules->flatMap(fn($m) => $m->lessons)->count();
        $totalMinutes = $course->modules->flatMap(fn($m) => $m->lessons)->sum('duration_minutes');

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $course->id,
                'slug'              => $course->slug,
                'title'             => $course->title,
                'short_description' => $course->short_description,
                'description'       => $course->description,
                'thumbnail'         => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'preview_video_url' => $course->preview_video_url,
                'price'             => $course->price,
                'discount_price'    => $course->discount_price,
                'course_type'       => $course->course_type,
                'language'          => $course->language,
                'duration'          => $course->duration,
                'duration_hours'    => $course->duration_hours,
                'category'          => ['id' => $course->category?->id, 'name' => $course->category?->name],
                'level'             => ['id' => $course->level?->id, 'name' => $course->level?->name],
                'instructor'        => $course->expert ? [
                    'id'   => $course->expert->id,
                    'name' => $course->expert->first_name . ' ' . $course->expert->last_name,
                    'title'=> 'Industry Expert', // Could map to ExpertProfile title
                ] : null,
                'enrolled_count'    => CourseEnrollment::where('course_id', $course->id)->count(),
                'total_lessons'     => $totalLessons,
                'total_minutes'     => $totalMinutes,
                'curriculum'        => $course->modules->map(fn($m) => [
                    'id'      => $m->id,
                    'title'   => $m->title,
                    'order'   => $m->order,
                    'lessons' => $m->lessons->map(fn($l) => [
                        'id'               => $l->id,
                        'title'            => $l->title,
                        'type'             => $l->type,
                        'duration_minutes' => $l->duration_minutes,
                        // Content is locked — students see it after enrollment
                    ]),
                ]),
            ]
        ]);
    }

    /**
     * Check enrollment status for a specific course (requires auth)
     * GET /api/public/courses/{slug}/enroll-status
     */
    public function enrollStatus(Request $request, $slug)
    {
        $course = Course::where('slug', $slug)->firstOrFail();

        $enrollment = null;
        $isEnrolled = false;

        if ($request->user()) {
            $enrollment = CourseEnrollment::where('user_id', $request->user()->id)
                ->where('course_id', $course->id)
                ->first();
            $isEnrolled = $enrollment !== null && $enrollment->status === 'active';
        }

        return response()->json([
            'success'     => true,
            'is_enrolled' => $isEnrolled,
            'status'      => $enrollment?->status ?? null,
        ]);
    }
}
