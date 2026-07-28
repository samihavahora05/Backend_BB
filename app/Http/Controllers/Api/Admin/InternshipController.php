<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternshipRequest;
use App\Repositories\Contracts\InternshipRepositoryInterface;
use App\Services\InternshipService;
use Illuminate\Http\Request;

class InternshipController extends Controller
{
    protected $repository;
    protected $service;

    public function __construct(InternshipRepositoryInterface $repository, InternshipService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $internships = $this->repository->getAllInternships($request->all(), $request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $internships
        ]);
    }

    public function store(InternshipRequest $request)
    {
        $internship = $this->repository->createInternship($request->validated());
        return response()->json(['success' => true, 'data' => $internship], 201);
    }

    public function show($id)
    {
        $internship = $this->repository->getInternshipById($id);
        return response()->json(['success' => true, 'data' => $internship]);
    }

    public function update(InternshipRequest $request, $id)
    {
        $internship = $this->repository->updateInternship($id, $request->validated());
        return response()->json(['success' => true, 'data' => $internship]);
    }

    public function destroy($id)
    {
        $this->repository->deleteInternship($id);
        return response()->json(['success' => true, 'message' => 'Internship deleted']);
    }

    public function duplicate(Request $request, $id)
    {
        $newInternship = $this->service->duplicateInternship($id, $request->user()->id);
        return response()->json(['success' => true, 'data' => $newInternship]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:internships,id',
            'status' => 'required|in:open,closed,draft,archived'
        ]);

        $this->repository->bulkUpdateStatus($request->ids, $request->status);
        return response()->json(['success' => true, 'message' => 'Statuses updated']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:internships,id',
        ]);

        $this->repository->bulkDelete($request->ids);
        return response()->json(['success' => true, 'message' => 'Internships deleted']);
    }

    public function stats(): \Illuminate\Http\JsonResponse
    {
        $stats = [
            'total'       => \App\Models\Internship::count(),
            'open'        => \App\Models\Internship::where('status', 'open')->count(),
            'draft'       => \App\Models\Internship::where('status', 'draft')->count(),
            'closed'      => \App\Models\Internship::where('status', 'closed')->count(),
            'archived'    => \App\Models\Internship::where('status', 'archived')->count(),
            'applications' => \App\Models\InternshipApplication::count(),
            'pending'     => \App\Models\InternshipApplication::where('status', 'pending')->count(),
            'approved'    => \App\Models\InternshipApplication::where('status', 'approved')->count(),
            'rejected'    => \App\Models\InternshipApplication::where('status', 'rejected')->count(),
            'submissions' => \App\Models\InternshipSubmission::count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $internships = \App\Models\Internship::with('company')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $format = $request->get('format', 'csv');

        if ($format === 'csv') {
            $headers = ['ID', 'Title', 'Company', 'Status', 'Mode', 'Stipend', 'Openings', 'Start Date', 'End Date', 'Applications', 'Created At'];
            $rows    = $internships->map(fn($i) => [
                $i->id,
                $i->title,
                $i->company?->first_name . ' ' . $i->company?->last_name,
                $i->status,
                $i->mode,
                $i->stipend,
                $i->openings,
                $i->start_date?->format('Y-m-d'),
                $i->end_date?->format('Y-m-d'),
                $i->applications()->count(),
                $i->created_at->format('Y-m-d'),
            ]);

            $csv = implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
            }

            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="internships-export.csv"',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unsupported format'], 400);
    }
}

