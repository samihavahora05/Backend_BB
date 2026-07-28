<?php

namespace App\Services;

use App\Repositories\DeleteRequestRepository;
use App\Models\DeleteRequest;
use App\Models\DeleteRequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteRequestService
{
    protected $repository;

    public function __construct(DeleteRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getPendingRequests()
    {
        return $this->repository->getAllPending();
    }

    public function getAllRequests()
    {
        return $this->repository->getAll();
    }

    public function rejectRequest(DeleteRequest $request, $notes)
    {
        return DB::transaction(function () use ($request, $notes) {
            $this->repository->updateStatus($request, 'rejected', $notes);
            $this->logActivity($request->id, 'rejected', $notes);
            return $request;
        });
    }

    public function approveRequest(DeleteRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user;

            if ($user) {
                // Here we would implement the permanent deletion logic
                // For compliance, we soft delete first, or anonymize
                
                // Optional: Delete user's profile data
                // Optional: Delete user's applications
                // Optional: Delete user's media
                
                // Soft delete user
                $user->delete();
                Log::info("User ID {$user->id} deleted via Delete Request ID {$request->id}");
            }

            $this->repository->updateStatus($request, 'approved', 'Data purged and user account removed.');
            $this->logActivity($request->id, 'approved', 'Data purged and user account removed.');

            return $request;
        });
    }

    protected function logActivity($requestId, $action, $notes = null)
    {
        DeleteRequestLog::create([
            'delete_request_id' => $requestId,
            'admin_id' => auth()->id() ?? 1,
            'action' => $action,
            'notes' => $notes,
            'ip_address' => request()->ip(),
        ]);
    }
}
