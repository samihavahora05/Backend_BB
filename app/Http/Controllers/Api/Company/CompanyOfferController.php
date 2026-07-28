<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobOffer;
use Carbon\Carbon;

class CompanyOfferController extends Controller
{
    /**
     * Get all offers sent by the company
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->id;
        
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        $applicationIds = JobApplication::whereIn('job_id', $jobIds)->pluck('id');
        
        $offers = JobOffer::whereIn('application_id', $applicationIds)
            ->with(['application.user', 'application.job'])
            ->latest()
            ->get()
            ->map(function($offer) {
                return [
                    'id' => $offer->id,
                    'applicationId' => $offer->application_id,
                    'name' => $offer->application && $offer->application->user ? $offer->application->user->name : 'Unknown',
                    'role' => $offer->application && $offer->application->job ? $offer->application->job->title : 'Unknown Role',
                    'salary_offered' => $offer->salary_offered,
                    'offer_letter' => $offer->offer_letter_path ? asset('storage/' . $offer->offer_letter_path) : null,
                    'valid_until' => $offer->valid_until ? Carbon::parse($offer->valid_until)->format('M d, Y') : null,
                    'status' => $offer->status, // pending, accepted, declined, expired
                    'sent_at' => $offer->created_at->format('M d, Y')
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    /**
     * Create/Send a new job offer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:job_applications,id',
            'salary_offered' => 'required|numeric',
            'valid_until' => 'nullable|date',
            'offer_letter' => 'nullable|file|mimes:pdf|max:2048'
        ]);

        $companyId = $request->user()->id;
        $jobIds = Job::where('company_id', $companyId)->pluck('id');
        
        $application = JobApplication::whereIn('job_id', $jobIds)->findOrFail($validated['application_id']);

        $offer = new JobOffer();
        $offer->application_id = $application->id;
        $offer->salary_offered = $validated['salary_offered'];
        $offer->valid_until = $validated['valid_until'] ? Carbon::parse($validated['valid_until']) : Carbon::now()->addDays(7);
        $offer->status = 'pending';
        
        if ($request->hasFile('offer_letter')) {
            $path = $request->file('offer_letter')->store('offers', 'public');
            $offer->offer_letter_path = $path;
        }
        
        $offer->save();
        
        // Update application status
        $application->status = 'offer_sent';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Job offer sent successfully.',
            'data' => $offer
        ], 201);
    }
}
