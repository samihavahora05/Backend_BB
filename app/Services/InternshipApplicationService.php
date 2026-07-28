<?php

namespace App\Services;

use App\Repositories\Contracts\InternshipRepositoryInterface;

class InternshipApplicationService
{
    protected InternshipRepositoryInterface $repository;
    protected InternshipService $internshipService;

    public function __construct(InternshipRepositoryInterface $repository, InternshipService $internshipService)
    {
        $this->repository = $repository;
        $this->internshipService = $internshipService;
    }

    public function processStatusUpdate(int $applicationId, string $newStatus, int $adminId, ?string $notes = null)
    {
        $application = $this->repository->updateApplicationStatus($applicationId, $newStatus, $notes);
        
        $this->internshipService->logActivity(
            $application->internship_id, 
            "Application Status updated to $newStatus for Student ID: {$application->user_id}", 
            $adminId
        );

        // Here we could dispatch an Email/Notification Job to the student
        // e.g. dispatch(new \App\Jobs\SendInternshipStatusEmail($application));

        if ($newStatus === 'offer_sent') {
            $this->generateOfferLetter($application);
        }

        if ($newStatus === 'completed') {
            $this->generateCompletionCertificate($application);
        }

        return $application;
    }

    protected function generateOfferLetter($application)
    {
        // Placeholder for PDF generation
        // In production, use Barryvdh\DomPDF to generate and save to Storage
        $dummyUrl = url("/storage/internships/offers/{$application->id}_offer.pdf");
        
        \App\Models\InternshipDocument::updateOrCreate(
            ['internship_id' => $application->internship_id, 'user_id' => $application->user_id, 'document_type' => 'offer_letter'],
            ['file_path' => $dummyUrl]
        );
    }

    protected function generateCompletionCertificate($application)
    {
        // Placeholder for PDF generation
        $dummyUrl = url("/storage/internships/certificates/{$application->id}_cert.pdf");
        
        \App\Models\InternshipDocument::updateOrCreate(
            ['internship_id' => $application->internship_id, 'user_id' => $application->user_id, 'document_type' => 'certificate'],
            ['file_path' => $dummyUrl]
        );
    }
}
