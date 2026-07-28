<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;

class CompanyInternshipController extends Controller
{
    /**
     * Get all internships for the company (Jobs with employment_type = Internship)
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $internships = Job::where('company_id', $companyId)
            ->where('employment_type', 'Internship')
            ->latest()
            ->withCount('applications')
            ->get()
            ->map(function($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'department' => $job->department,
                    'mode' => $job->remote_type,
                    'location' => $job->location,
                    'duration' => 'N/A', // Not supported in Job model
                    'stipend' => $job->salary_min ? '₹' . number_format($job->salary_min) . '/mo' : 'Unpaid',
                    'status' => $job->status,
                    'applicants' => $job->applications_count,
                    'posted' => $job->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $internships
        ]);
    }
    
    public function updateStatus(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)
                  ->where('employment_type', 'Internship')
                  ->findOrFail($id);
                  
        $validated = $request->validate([
            'status' => 'required|string|in:draft,active,closed'
        ]);
        
        $job->status = $validated['status'];
        $job->save();
        
        return response()->json(['success' => true]);
    }
    
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->id;
        $job = Job::where('company_id', $companyId)
                  ->where('employment_type', 'Internship')
                  ->findOrFail($id);
                  
        $job->delete();
        
        return response()->json(['success' => true]);
    }
}
