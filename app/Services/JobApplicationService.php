<?php

namespace App\Services;

use App\Repositories\Contracts\JobRepositoryInterface;
use App\Models\JobActivityLog;
use App\Models\JobApplication;
use App\Models\JobInterview;
use App\Models\JobOffer;
use Illuminate\Support\Facades\DB;

class JobApplicationService
{
    protected $repository;

    public function __construct(JobRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function logJobActivity(int $jobId, int $userId, string $action, ?string $notes = null)
    {
        JobActivityLog::create([
            'job_id' => $jobId,
            'user_id' => $userId,
            'action' => $action,
            'notes' => $notes
        ]);
    }

    public function updateApplicationStatus(int $applicationId, string $status, int $adminId, ?string $notes = null)
    {
        return DB::transaction(function () use ($applicationId, $status, $adminId, $notes) {
            $application = $this->repository->getApplicationById($applicationId);
            $oldStatus = $application->status;
            
            $application->update(['status' => $status]);
            
            $this->logJobActivity(
                $application->job_id, 
                $adminId, 
                "Application #{$applicationId} status changed from {$oldStatus} to {$status}", 
                $notes
            );
            
            return $application;
        });
    }

    public function scheduleInterview(int $applicationId, array $data, int $adminId)
    {
        return DB::transaction(function () use ($applicationId, $data, $adminId) {
            $interview = JobInterview::create([
                'application_id' => $applicationId,
                'interviewer_id' => $data['interviewer_id'] ?? $adminId,
                'mode' => $data['mode'] ?? 'google_meet',
                'meeting_link' => $data['meeting_link'] ?? null,
                'location' => $data['location'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'round_number' => $data['round_number'] ?? 1,
            ]);

            $this->updateApplicationStatus($applicationId, 'interview_scheduled', $adminId, "Interview Round {$interview->round_number} Scheduled");

            return $interview;
        });
    }
    
    public function gradeInterview(int $interviewId, array $data, int $adminId)
    {
        $interview = JobInterview::findOrFail($interviewId);
        $interview->update([
            'marks_obtained' => $data['marks_obtained'] ?? $interview->marks_obtained,
            'feedback' => $data['feedback'] ?? null,
            'recommendation' => $data['recommendation'] ?? 'pending',
        ]);
        
        $this->logJobActivity(
            $interview->application->job_id, 
            $adminId, 
            "Interview #{$interviewId} Graded: {$interview->recommendation}"
        );
        
        return $interview;
    }

    public function generateOffer(int $applicationId, array $data, int $adminId)
    {
        return DB::transaction(function () use ($applicationId, $data, $adminId) {
            $offer = JobOffer::create([
                'application_id' => $applicationId,
                'salary_offered' => $data['salary_offered'] ?? null,
                'offer_letter_path' => $data['offer_letter_path'] ?? null, // Could generate PDF here
                'valid_until' => $data['valid_until'] ?? null,
                'status' => 'pending'
            ]);

            $this->updateApplicationStatus($applicationId, 'offer_sent', $adminId, "Offer Generated and Sent");

            return $offer;
        });
    }
}
