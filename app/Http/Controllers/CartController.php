<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Course;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = Cart::with('course')
            ->where('user_id', $request->user()->id)
            ->get();
            
        return response()->json([
            'items' => $cart,
            'total_price' => $cart->sum('price')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $exists = Cart::where('user_id', $request->user()->id)
            ->where('course_id', $request->course_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Course already in cart'], 409);
        }

        $course = Course::findOrFail($request->course_id);

        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'course_id' => $request->course_id,
            'price' => $course->discount_price ?? $course->price
        ]);

        return response()->json($cart, 201);
    }

    public function destroy(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)->findOrFail($id);
        $cart->delete();
        
        return response()->json(['message' => 'Removed from cart']);
    }
}
