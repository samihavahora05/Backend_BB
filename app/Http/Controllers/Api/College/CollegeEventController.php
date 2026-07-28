<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use Illuminate\Http\Request;

class CollegeEventController extends Controller
{
    public function index(Request $request)
    {
        $events = Contest::where('college_id', $request->user()->id)
            ->withCount('registrations')
            ->latest()
            ->get();
        return response()->json(['success' => true, 'data' => $events]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $event = Contest::create(array_merge($validated, [
            'college_id' => $request->user()->id,
            'status' => 'active',
            'category_id' => 1 // Default to hackathon/event category
        ]));

        return response()->json(['success' => true, 'data' => $event]);
    }

    public function update(Request $request, $id)
    {
        $event = Contest::where('college_id', $request->user()->id)->findOrFail($id);
        $event->update($request->all());
        return response()->json(['success' => true, 'data' => $event]);
    }

    public function destroy(Request $request, $id)
    {
        $event = Contest::where('college_id', $request->user()->id)->findOrFail($id);
        $event->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }
}
