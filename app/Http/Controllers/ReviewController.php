<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;

class ReviewController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to get reviews for a specific course
     */
    public function index(Request $request)
    {
        $request->validate(['course_id' => 'required|exists:courses,id']);
        
        $query = Review::where('course_id', $request->course_id)
            ->where('is_approved', true)
            ->with('user:id,name');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['rating', 'created_at'],
            ['comment']
        );
            
        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Auth method to submit a review
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);
        
        // Check if user already reviewed
        $existing = Review::where('course_id', $request->course_id)
            ->where('user_id', $request->user()->id)
            ->first();
            
        if ($existing) {
            return response()->json(['message' => 'You have already reviewed this course.'], 400);
        }

        $review = Review::create([
            'course_id' => $request->course_id,
            'user_id' => $request->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => true // Default to true for now
        ]);

        return response()->json(['message' => 'Review submitted successfully', 'data' => $review], 201);
    }

    /**
     * Admin method to update review status (e.g., hide it)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'is_approved' => 'required|boolean'
        ]);

        $review = Review::findOrFail($id);
        $review->update($validated);

        return response()->json(['message' => 'Review status updated successfully', 'data' => $review]);
    }

    /**
     * Admin method to delete a review
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }
}
