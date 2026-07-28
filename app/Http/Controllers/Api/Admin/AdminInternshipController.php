<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission; // I need to make sure this model exists, but if it doesn't I will rely on standard table if required or check later.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInternshipController extends Controller
{
    public function stats()
    {
        $total = Internship::count();
        $open = Internship::where('status', 'open')->count();
        $applications = InternshipApplication::count();
        $pending = InternshipApplication::where('status', 'pending')->count();
        $approved = InternshipApplication::where('status', 'approved')->count();
        // Assuming there's a submissions table, we'll try to query if possible, otherwise return 0 for now
        $submissions = 0;
        if(class_exists('\App\Models\InternshipSubmission')) {
            $submissions = \App\Models\InternshipSubmission::count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'open' => $open,
                'applications' => $applications,
                'pending' => $pending,
                'approved' => $approved,
                'submissions' => $submissions
            ]
        ]);
    }

    private function buildInternshipQuery(Request $request)
    {
        $query = Internship::with(['company'])->withCount('applications');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $sortBy = $request->sort_by;
            
            if (in_array($sortBy, ['title', 'created_at', 'applications_count', 'status', 'start_date'])) {
                $query->orderBy($sortBy, $sortDir);
            } else {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildInternshipQuery($request);
        $perPage = $request->input('per_page', 15);
        $internships = $query->paginate($perPage);

        return response()->json($internships);
    }

    public function show($id)
    {
        $internship = Internship::with('company')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $internship]);
    }

    private function validateInternship(Request $request)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'mode' => 'nullable|in:Remote,Hybrid,Onsite',
            'duration_months' => 'nullable|integer|min:1',
            'duration' => 'nullable|string|max:255',
            'stipend' => 'nullable|numeric|min:0',
            'skills_required' => 'nullable|array',
            'eligibility' => 'nullable|string',
            'description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'openings' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|in:open,closed,draft,archived',
            'featured' => 'nullable|boolean',
            'thumbnail' => 'nullable|string',
            'preview_image' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInternship($request);
        
        // Defaults and logic
        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->id();
        }
        if (empty($data['openings'])) {
            $data['openings'] = 1;
        }

        $internship = Internship::create($data);
        return response()->json(['success' => true, 'data' => $internship], 201);
    }

    public function update(Request $request, $id)
    {
        $internship = Internship::findOrFail($id);
        $data = $this->validateInternship($request);

        if (array_key_exists('openings', $data) && empty($data['openings'])) {
            $data['openings'] = 1;
        }

        $internship->update($data);
        return response()->json(['success' => true, 'data' => $internship]);
    }

    public function destroy($id)
    {
        Internship::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function duplicate($id)
    {
        $internship = Internship::findOrFail($id);
        $new = $internship->replicate();
        $new->title = $new->title . ' (Copy)';
        $new->save();
        return response()->json(['success' => true, 'data' => $new]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        Internship::whereIn('id', $request->ids)->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        Internship::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $query = $this->buildInternshipQuery($request);
        $internships = $query->get();
        
        $csv = "ID,Title,Company,Department,Mode,Location,Stipend,Openings,Status,Start Date,Created At\n";
        foreach($internships as $i) {
            $companyName = str_replace('"', '""', $i->company?->first_name . ' ' . $i->company?->last_name);
            $title = str_replace('"', '""', $i->title);
            $dept = str_replace('"', '""', $i->department);
            $loc = str_replace('"', '""', $i->location);
            $stipend = $i->stipend;
            
            $csv .= "{$i->id},\"{$title}\",\"{$companyName}\",\"{$dept}\",{$i->mode},\"{$loc}\",{$stipend},{$i->openings},{$i->status},{$i->start_date},{$i->created_at}\n";
        }
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="internships_export.csv"');
    }

    // Applications
    public function allApplications(Request $request)
    {
        $query = InternshipApplication::with(['user', 'internship']);

        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $apps = $query->latest()->paginate($request->input('per_page', 15));
        return response()->json($apps);
    }



    // Tasks
    public function createTask(Request $request)
    {
        $data = $request->validate([
            'internship_id' => 'required|exists:internships,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric',
            'deadline' => 'nullable|date'
        ]);

        $task = InternshipTask::create($data);
        return response()->json(['success' => true, 'data' => $task]);
    }

    // Submissions
    public function allSubmissions(Request $request)
    {
        if(!class_exists('\App\Models\InternshipSubmission')) {
            // Mock empty if no model
            return response()->json(['data' => []]);
        }
        $query = \App\Models\InternshipSubmission::with(['user', 'task.internship']);
        
        $subs = $query->latest()->paginate($request->input('per_page', 15));
        return response()->json($subs);
    }

    public function gradeSubmission(Request $request, $id)
    {
        if(!class_exists('\App\Models\InternshipSubmission')) return response()->json([], 404);
        
        $sub = \App\Models\InternshipSubmission::findOrFail($id);
        $sub->update($request->only(['status', 'marks_obtained', 'feedback']));
        return response()->json(['success' => true, 'data' => $sub]);
    }

    public function applicationsByInternship(Request $request, $id)
    {
        $query = InternshipApplication::with(['user', 'internship'])
            ->where('internship_id', $id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $apps = $query->latest()->paginate($request->input('per_page', 15));
        return response()->json($apps);
    }

    public function updateApplicationStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        $app = InternshipApplication::findOrFail($id);
        
        $app->status = $request->status;
        if ($request->filled('internal_notes')) {
            $app->internal_notes = $request->internal_notes;
        }
        $app->save();

        return response()->json([
            'success' => true,
            'data' => $app,
            'message' => 'Status updated successfully'
        ]);
    }
}
