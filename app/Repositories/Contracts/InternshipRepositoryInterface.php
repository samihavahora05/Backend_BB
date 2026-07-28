<?php

namespace App\Repositories\Contracts;

interface InternshipRepositoryInterface
{
    public function getAllInternships(array $filters = [], int $perPage = 15);
    public function getInternshipById(int $id);
    public function createInternship(array $data);
    public function updateInternship(int $id, array $data);
    public function deleteInternship(int $id);
    
    public function duplicateInternship(int $id): \App\Models\Internship;
    public function bulkUpdateStatus(array $ids, string $status);
    public function bulkDelete(array $ids);
    
    // Application methods
    public function getAllApplications(array $filters = [], int $perPage = 15);
    public function getApplicationsForInternship(int $internshipId, array $filters = [], int $perPage = 15);
    public function getApplicationById(int $id);
    public function updateApplicationStatus(int $id, string $status, ?string $internalNotes = null);
    
    // Tasks & Submissions
    public function getTasksForInternship(int $internshipId);
    public function createTask(array $data);
    public function gradeSubmission(int $submissionId, array $data);
    public function getAllSubmissions(array $filters = [], int $perPage = 15);
}
