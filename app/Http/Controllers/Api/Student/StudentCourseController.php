<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use App\Models\Course;
use App\Models\IssuedCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentCourseController extends Controller
{
    /**
     * Enroll authenticated student in a course
     */
    public function enroll(Request $request, $course_id)
    {
        $user = $request->user();
        $course = Course::where('status', 'Published')->findOrFail($course_id);

        $exists = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Already enrolled'], 400);
        }

        $enrollment = CourseEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Successfully enrolled!', 'data' => $enrollment]);
    }

    /**
     * Get all courses enrolled by the authenticated student
     */
    public function index(Request $request)
    {
        $enrollments = CourseEnrollment::with(['course.category', 'course.modules.lessons'])
            ->where('user_id', $request->user()->id)
            ->get();

        $active = [];
        $completed = [];

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            
            // Calculate progress dynamically
            $totalLessons = 0;
            $completedLessons = 0;
            
            foreach ($course->modules as $module) {
                $totalLessons += $module->lessons->count();
                foreach ($module->lessons as $lesson) {
                    $isCompleted = LessonProgress::where('user_id', $request->user()->id)
                        ->where('lesson_id', $lesson->id)
                        ->where('is_completed', true)
                        ->exists();
                    if ($isCompleted) {
                        $completedLessons++;
                    }
                }
            }

            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            $isCourseCompleted = $enrollment->status === 'completed' || $progress >= 100;

            $courseData = [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'title' => $course->title,
                'thumbnail' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'category' => $course->category->name ?? 'Uncategorized',
                'progress' => $progress,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'instructor' => $course->instructor ? $course->instructor->name : 'Expert', // Assuming relation or fallback
                'completed_date' => $isCourseCompleted ? $enrollment->updated_at->format('M d, Y') : null,
                'next_module' => 'Continue Learning' // Placeholder logic
            ];

            if ($isCourseCompleted) {
                $completed[] = $courseData;
            } else {
                $active[] = $courseData;
            }
        }

        return response()->json([
            'success' => true, 
            'data' => [
                'active' => $active,
                'completed' => $completed
            ]
        ]);
    }

    /**
     * Get details of a single enrolled course with curriculum and progress
     */
    public function show(Request $request, $course_id)
    {
        $user = $request->user();

        // Verify enrollment
        $enrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['success' => false, 'message' => 'Not enrolled in this course'], 403);
        }

        $course = Course::with(['category', 'level', 'expert', 'modules' => function($q) {
            $q->orderBy('order');
        }, 'modules.lessons' => function($q) {
            $q->orderBy('order');
        }])->findOrFail($course_id);

        // Fetch completed lesson IDs
        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereHas('lesson.module', function($q) use ($course_id) {
                $q->where('course_id', $course_id);
            })
            ->pluck('lesson_id')
            ->toArray();

        $curriculum = $course->modules->map(function ($module, $mIdx) use ($completedLessonIds) {
            return [
                'id' => $module->id,
                'module' => $module->title,
                'order' => $module->order,
                'lessons' => $module->lessons->map(function ($lesson, $lIdx) use ($completedLessonIds, $mIdx) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'duration' => $lesson->duration_minutes . ' min',
                        'isFree' => $lesson->type === 'video', // Adjust as needed
                        'videoUrl' => $lesson->video_url, // No fallback
                        'isCompleted' => in_array($lesson->id, $completedLessonIds),
                        'mIdx' => $mIdx,
                        'lIdx' => $lIdx,
                    ];
                }),
            ];
        });

        $totalLessons = $course->modules->flatMap(function($m) { return $m->lessons; })->count();
        $progress = $totalLessons > 0 ? round((count($completedLessonIds) / $totalLessons) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'instructor' => [
                    'name' => $course->expert ? $course->expert->name : 'Instructor',
                    'title' => 'Senior Instructor', // Default or fetch if available
                    'avatar' => $course->expert && $course->expert->profile_photo_path ? asset('storage/' . $course->expert->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($course->expert ? $course->expert->name : 'Instructor') . '&background=C9A227&color=fff',
                ],
                'duration' => $course->duration ?? 'N/A',
                'level' => $course->level?->title ?? 'Beginner',
                'curriculum' => $curriculum,
                'completed_lesson_ids' => $completedLessonIds,
                'progress' => $progress,
                'total_lessons' => $totalLessons
            ]
        ]);
    }

    /**
     * Mark a specific lesson as completed
     */
    public function markLessonComplete(Request $request, $course_id, $lesson_id)
    {
        $user = $request->user();

        // Verify enrollment
        $isEnrolled = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course_id)
            ->where('status', 'active')
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['success' => false, 'message' => 'Not enrolled in this course'], 403);
        }

        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson_id],
            ['is_completed' => true]
        );

        // Check overall course progress to issue certificate
        $course = Course::with('modules.lessons')->findOrFail($course_id);
        
        $totalLessons = 0;
        $completedLessons = 0;
        
        foreach ($course->modules as $module) {
            $totalLessons += $module->lessons->count();
            foreach ($module->lessons as $lesson) {
                $isCompleted = LessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->where('is_completed', true)
                    ->exists();
                if ($isCompleted) {
                    $completedLessons++;
                }
            }
        }

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        // Auto-generate certificate if 100% complete
        if ($progress >= 100) {
            $existingCert = IssuedCertificate::where('user_id', $user->id)
                ->where('course_id', $course_id)
                ->first();

            if (!$existingCert) {
                IssuedCertificate::create([
                    'user_id' => $user->id,
                    'course_id' => $course_id,
                    'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                    'issued_at' => now(),
                    'status' => 'Issued'
                ]);
            }
        }

        return response()->json([
            'success' => true, 
            'message' => 'Lesson marked as complete', 
            'course_progress' => $progress
        ]);
    }
}
