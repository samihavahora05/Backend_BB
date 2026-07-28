<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;
use App\Models\Internship;
use App\Mail\InternshipApplicationMail;
use App\Jobs\SendQueuedEmailJob;

class InternshipApplicationController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = InternshipApplication::with(['internship', 'student.studentProfile'])
            ->when($request->internship_id, function($q, $internship_id) {
                $q->where('internship_id', $internship_id);
            });

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['status', 'created_at'],
            ['status']
        );
            
        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'internship_id' => 'required|exists:internships,id',
            'resume_path' => 'nullable|string'
        ]);

        $internship = Internship::with('company')->findOrFail($request->internship_id);

        $application = InternshipApplication::create([
            'internship_id' => $request->internship_id,
            'student_id' => $request->user()->id,
            'resume_path' => $request->resume_path,
            'status' => 'applied'
        ]);

        // 1. Notify user (DB & Push)
        $request->user()->notify(new PlatformNotification(
            "Application Submitted! 💼",
            "You have applied for the internship: '{$internship->title}' at {$internship->company->name}.",
            'internship_applied',
            ['internship_id' => $internship->id, 'application_id' => $application->id]
        ));

        // 2. Dispatch queued email confirmation
        SendQueuedEmailJob::dispatch(
            $request->user()->email,
            new InternshipApplicationMail($internship->title, $internship->company->name ?? 'Blueboxx Partner', now()->toDateString(), 'applied'),
            'Internship Application Confirmation'
        );

        return response()->json($application, 201);
    }


    public function show($id)
    {
        return response()->json(InternshipApplication::with(['internship', 'student'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $application = InternshipApplication::findOrFail($id);
        
        $request->validate(['status' => 'required|in:applied,shortlisted,rejected,hired']);
        
        $application->update(['status' => $request->status]);
        return response()->json($application);
    }

    public function destroy($id)
    {
        InternshipApplication::findOrFail($id)->delete();
        return response()->json(['message' => 'Application withdrawn']);
    }
}
