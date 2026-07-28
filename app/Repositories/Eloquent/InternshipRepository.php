<?php

namespace App\Repositories\Eloquent;

use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission;
use App\Repositories\Contracts\InternshipRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InternshipRepository implements InternshipRepositoryInterface
{
    public function getAllInternships(array $filters = [], int $perPage = 15)
    {
        $query = Internship::with(['company'])->withCount('applications');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getInternshipById(int $id)
    {
        return Internship::with(['company', 'tasks', 'evaluations', 'applications.user'])->findOrFail($id);
    }

    public function createInternship(array $data)
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->id() ?? 1;
        }
        return Internship::create($data);
    }

    public function updateInternship(int $id, array $data)
    {
        $internship = $this->getInternshipById($id);
        $internship->update($data);
        return $internship;
    }

    public function deleteInternship(int $id)
    {
        return $this->getInternshipById($id)->delete();
    }

    public function duplicateInternship(int $id): Internship
    {
        return DB::transaction(function () use ($id) {
            $original = $this->getInternshipById($id);
            $newInternship = $original->replicate();
            $newInternship->title = $original->title . ' (Copy)';
            $newInternship->status = 'draft';
            $newInternship->created_at = now();
            $newInternship->save();
            
            return $newInternship;
        });
    }

    public function bulkUpdateStatus(array $ids, string $status)
    {
        return Internship::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function bulkDelete(array $ids)
    {
        return Internship::whereIn('id', $ids)->delete();
    }

    public function getAllApplications(array $filters = [], int $perPage = 15)
    {
        $query = InternshipApplication::with(['user', 'internship']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getApplicationsForInternship(int $internshipId, array $filters = [], int $perPage = 15)
    {
        $query = InternshipApplication::with(['user'])->where('internship_id', $internshipId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getApplicationById(int $id)
    {
        return InternshipApplication::with(['user', 'internship'])->findOrFail($id);
    }

    public function updateApplicationStatus(int $id, string $status, ?string $internalNotes = null)
    {
        $application = $this->getApplicationById($id);
        $application->status = $status;
        if ($internalNotes !== null) {
            $application->internal_notes = $internalNotes;
        }
        $application->save();
        return $application;
    }

    public function getTasksForInternship(int $internshipId)
    {
        return InternshipTask::with(['submissions.user'])->where('internship_id', $internshipId)->get();
    }

    public function createTask(array $data)
    {
        return InternshipTask::create($data);
    }

    public function gradeSubmission(int $submissionId, array $data)
    {
        $submission = InternshipSubmission::findOrFail($submissionId);
        $submission->update($data);
        return $submission;
    }

    public function getAllSubmissions(array $filters = [], int $perPage = 15)
    {
        $query = InternshipSubmission::with(['user', 'task.internship']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
