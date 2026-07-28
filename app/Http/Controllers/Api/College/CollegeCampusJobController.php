<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class CollegeCampusJobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::where('college_id', $request->user()->id)
            ->where('drive_type', 'campus_job')
            ->withCount('applications')
            ->latest()
            ->get();
        return response()->json(['success' => true, 'data' => $jobs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'vacancies' => 'nullable|integer',
            'application_deadline' => 'nullable|date',
            'location' => 'nullable|string',
        ]);

        $job = Job::create(array_merge($validated, [
            'college_id' => $request->user()->id,
            'drive_type' => 'campus_job',
            'status' => 'active'
        ]));

        return response()->json(['success' => true, 'data' => $job]);
    }

    public function update(Request $request, $id)
    {
        $job = Job::where('college_id', $request->user()->id)
            ->where('drive_type', 'campus_job')
            ->findOrFail($id);
            
        $job->update($request->all());
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function destroy(Request $request, $id)
    {
        $job = Job::where('college_id', $request->user()->id)
            ->where('drive_type', 'campus_job')
            ->findOrFail($id);
            
        $job->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }
}
