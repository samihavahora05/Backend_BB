<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\JobBookmark;
use App\Models\SavedInternship;

class StudentWishlistController extends Controller
{
    /**
     * Get all saved items.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Wishlist::with('course')->where('user_id', $user->id)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => 'course',
                'course' => $item->course,
                'saved_at' => $item->created_at->toIso8601String(),
            ];
        });

        $jobs = JobBookmark::with('job')->where('user_id', $user->id)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => 'job',
                'job' => $item->job,
                'saved_at' => $item->created_at->toIso8601String(),
            ];
        });

        $internships = SavedInternship::with('internship')->where('user_id', $user->id)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => 'internship',
                'internship' => $item->internship,
                'saved_at' => $item->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'courses'               => $courses,
                'jobs'                  => $jobs,
                'internships'           => $internships,
                'saved_job_ids'         => JobBookmark::where('user_id', $user->id)->pluck('job_id'),
                'saved_internship_ids'  => SavedInternship::where('user_id', $user->id)->pluck('internship_id'),
                'saved_course_ids'      => Wishlist::where('user_id', $user->id)->pluck('course_id'),
                'total'                 => $courses->count() + $jobs->count() + $internships->count()
            ]
        ]);
    }

    /**
     * Remove item from wishlist (by wishlist row id).
     */
    public function destroy(Request $request, $type, $id)
    {
        $user = $request->user();
        if ($type === 'course') {
            Wishlist::where('user_id', $user->id)->where('id', $id)->delete();
        } else if ($type === 'job') {
            JobBookmark::where('user_id', $user->id)->where('id', $id)->delete();
        } else if ($type === 'internship') {
            SavedInternship::where('user_id', $user->id)->where('id', $id)->delete();
        }
        return response()->json(['success' => true]);
    }

    // ─── Save / Unsave – Jobs ───────────────────────────────────────────────

    public function saveJob(Request $request, $id)
    {
        $user = $request->user();
        $exists = JobBookmark::where('user_id', $user->id)->where('job_id', $id)->first();
        if ($exists) {
            return response()->json(['success' => true, 'saved' => true]);
        }
        JobBookmark::create(['user_id' => $user->id, 'job_id' => $id]);
        return response()->json(['success' => true, 'saved' => true]);
    }

    public function unsaveJob(Request $request, $id)
    {
        $user = $request->user();
        JobBookmark::where('user_id', $user->id)->where('job_id', $id)->delete();
        return response()->json(['success' => true, 'saved' => false]);
    }

    // ─── Save / Unsave – Internships ────────────────────────────────────────

    public function saveInternship(Request $request, $id)
    {
        $user = $request->user();
        $exists = SavedInternship::where('user_id', $user->id)->where('internship_id', $id)->first();
        if ($exists) {
            return response()->json(['success' => true, 'saved' => true]);
        }
        SavedInternship::create(['user_id' => $user->id, 'internship_id' => $id]);
        return response()->json(['success' => true, 'saved' => true]);
    }

    public function unsaveInternship(Request $request, $id)
    {
        $user = $request->user();
        SavedInternship::where('user_id', $user->id)->where('internship_id', $id)->delete();
        return response()->json(['success' => true, 'saved' => false]);
    }

    // ─── Save / Unsave – Courses ────────────────────────────────────────────

    public function saveCourse(Request $request, $id)
    {
        $user = $request->user();
        $exists = Wishlist::where('user_id', $user->id)->where('course_id', $id)->first();
        if ($exists) {
            return response()->json(['success' => true, 'saved' => true]);
        }
        Wishlist::create(['user_id' => $user->id, 'course_id' => $id]);
        return response()->json(['success' => true, 'saved' => true]);
    }

    public function unsaveCourse(Request $request, $id)
    {
        $user = $request->user();
        Wishlist::where('user_id', $user->id)->where('course_id', $id)->delete();
        return response()->json(['success' => true, 'saved' => false]);
    }
}
