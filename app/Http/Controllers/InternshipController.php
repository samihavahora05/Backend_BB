<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\User;

class InternshipController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = Internship::with('company')
            ->where('status', 'open');

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['title', 'stipend', 'created_at'],
            ['title', 'description']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'duration_months' => 'required|integer',
        ]);

        $internship = Internship::create([
            'company_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'duration_months' => $request->duration_months,
            'stipend' => $request->stipend,
            'status' => $request->status ?? 'open',
        ]);

        // Send notifications to all students/interns
        $students = User::role(['student', 'intern'])->get();
        foreach ($students as $student) {
            $student->notify(new PlatformNotification(
                "New Internship Posted! 💼",
                "New internship available: '{$internship->title}' at " . $request->user()->name,
                'internship_posted',
                ['internship_id' => $internship->id]
            ));
        }

        return response()->json($internship, 201);
    }


    public function show($id)
    {
        return response()->json(Internship::with('company')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $internship = Internship::findOrFail($id);
        $internship->update($request->all());
        return response()->json($internship);
    }

    public function destroy($id)
    {
        Internship::findOrFail($id)->delete();
        return response()->json(['message' => 'Internship deleted']);
    }
}
