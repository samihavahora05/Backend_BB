<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Public method to get active FAQs
     */
    public function index()
    {
        $faqs = Faq::where('is_active', true)->orderBy('order', 'asc')->get();
        return response()->json($faqs);
    }

    /**
     * Admin method to create FAQ
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

        $faq = Faq::create($validated);

        return response()->json(['message' => 'FAQ created successfully', 'data' => $faq], 201);
    }

    /**
     * Admin method to show specific FAQ
     */
    public function show($id)
    {
        return response()->json(Faq::findOrFail($id));
    }

    /**
     * Admin method to update FAQ
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'question' => 'sometimes|string|max:255',
            'answer' => 'sometimes|string',
            'order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean'
        ]);

        $faq = Faq::findOrFail($id);
        $faq->update($validated);

        return response()->json(['message' => 'FAQ updated successfully', 'data' => $faq]);
    }

    /**
     * Admin method to delete FAQ
     */
    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted successfully']);
    }
}
