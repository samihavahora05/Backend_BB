<?php

namespace App\Services;

use App\Repositories\Contracts\InternshipRepositoryInterface;
use App\Models\InternshipActivityLog;

class InternshipService
{
    protected InternshipRepositoryInterface $repository;

    public function __construct(InternshipRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function logActivity(int $internshipId, string $action, ?int $userId = null, ?string $details = null)
    {
        InternshipActivityLog::create([
            'internship_id' => $internshipId,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
        ]);
    }

    public function duplicateInternship(int $id, int $adminId)
    {
        $newInternship = $this->repository->duplicateInternship($id);
        $this->logActivity($newInternship->id, 'Internship Duplicated', $adminId, "Duplicated from ID: $id");
        return $newInternship;
    }

    public function publishInternship(int $id, int $adminId)
    {
        $internship = $this->repository->updateInternship($id, ['status' => 'open']);
        $this->logActivity($id, 'Internship Published', $adminId);
        return $internship;
    }
}
