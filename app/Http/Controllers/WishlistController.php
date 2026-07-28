<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlist = Wishlist::with('course')
            ->where('user_id', $request->user()->id)
            ->get();
            
        return response()->json($wishlist);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('course_id', $request->course_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Course already in wishlist'], 409);
        }

        $wishlist = Wishlist::create([
            'user_id' => $request->user()->id,
            'course_id' => $request->course_id,
        ]);

        return response()->json($wishlist, 201);
    }

    public function destroy(Request $request, $id)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->findOrFail($id);
        $wishlist->delete();
        
        return response()->json(['message' => 'Removed from wishlist']);
    }
}
