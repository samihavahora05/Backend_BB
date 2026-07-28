<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicScholarshipController extends Controller
{
    /**
     * List active scholarship programs
     * GET /api/public/scholarships
     */
    public function index(Request $request)
    {
        $query = ScholarshipProgram::where('status', 'Active');

        if ($s = $request->query('search')) {
            $query->where('title', 'like', "%{$s}%");
        }

        $perPage = min((int)$request->query('per_page', 12), 50);
        $scholarships = $query->orderBy('deadline', 'asc')->paginate($perPage);

        $data = $scholarships->through(fn($s) => [
            'id'          => $s->id,
            'title'       => $s->title,
            'description' => \Str::limit($s->description, 100),
            'amount'      => $s->amount,
            'deadline'    => $s->deadline?->format('Y-m-d'),
            'status'      => $s->status,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]
        ]);
    }

    /**
     * Scholarship details
     * GET /api/public/scholarships/{id}
     */
    public function show(Request $request, $id)
    {
        $program = ScholarshipProgram::where('status', 'Active')->findOrFail($id);
        
        $hasApplied = false;
        if ($request->user()) {
            $hasApplied = ScholarshipApplication::where('program_id', $program->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $program->id,
                'title'       => $program->title,
                'description' => $program->description,
                'amount'      => $program->amount,
                'deadline'    => $program->deadline?->format('Y-m-d'),
                'status'      => $program->status,
                'has_applied' => $hasApplied,
            ]
        ]);
    }

    /**
     * Apply for a scholarship
     * POST /api/public/scholarships/{id}/apply
     */
    public function apply(Request $request, $id)
    {
        $program = ScholarshipProgram::where('status', 'Active')->findOrFail($id);

        if ($program->deadline && $program->deadline->isPast()) {
            return response()->json(['success' => false, 'message' => 'The deadline for this scholarship has passed.'], 400);
        }

        $alreadyApplied = ScholarshipApplication::where('program_id', $program->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['success' => false, 'message' => 'You have already applied for this scholarship.'], 400);
        }

        $request->validate([
            'essay'    => 'nullable|string|max:5000',
            'document' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $application = ScholarshipApplication::create([
                'program_id' => $program->id,
                'user_id'    => $request->user()->id,
                'status'     => 'Pending',
            ]);

            if ($request->hasFile('document')) {
                $path = $request->file('document')->store('scholarship_documents', 'public');
                ScholarshipDocument::create([
                    'application_id' => $application->id,
                    'document_type'  => 'Supporting Document',
                    'file_path'      => $path,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'data'    => ['application_id' => $application->id]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to submit application.'], 500);
        }
    }
}
