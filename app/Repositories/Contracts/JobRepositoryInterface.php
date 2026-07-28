<?php

namespace App\Repositories\Contracts;

interface JobRepositoryInterface
{
    public function getAllJobs(array $filters = [], int $perPage = 15);
    public function getJobById(int $jobId);
    public function createJob(array $data);
    public function updateJob(int $jobId, array $data);
    public function deleteJob(int $jobId);
    
    public function getJobApplications(int $jobId, array $filters = [], int $perPage = 15);
    public function getApplicationById(int $applicationId);
    
    // Dashboard metrics
    public function getDashboardMetrics();
}
