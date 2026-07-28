<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;

class TestimonialController extends Controller
{
    use PaginateQuery;

    /**
     * Public method to get featured testimonials
     */
    public function index(Request $request)
    {
        $query = Testimonial::where('is_featured', true);

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['name', 'rating', 'created_at'],
            ['name', 'content']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }


    /**
     * Admin method to create testimonial
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'required|string',
            'photo_url' => 'nullable|url',
            'rating' => 'required|integer|min:1|max:5',
            'is_featured' => 'boolean'
        ]);

        $testimonial = Testimonial::create($validated);

        return response()->json(['message' => 'Testimonial created successfully', 'data' => $testimonial], 201);
    }

    /**
     * Admin method to show specific testimonial
     */
    public function show($id)
    {
        return response()->json(Testimonial::findOrFail($id));
    }

    /**
     * Admin method to update testimonial
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'photo_url' => 'nullable|url',
            'rating' => 'sometimes|integer|min:1|max:5',
            'is_featured' => 'sometimes|boolean'
        ]);

        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update($validated);

        return response()->json(['message' => 'Testimonial updated successfully', 'data' => $testimonial]);
    }

    /**
     * Admin method to delete testimonial
     */
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();
        return response()->json(['message' => 'Testimonial deleted successfully']);
    }
}
