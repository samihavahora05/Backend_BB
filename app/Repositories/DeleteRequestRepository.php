<?php

namespace App\Repositories;

use App\Models\DeleteRequest;

class DeleteRequestRepository
{
    public function getAllPending()
    {
        return DeleteRequest::with('user')->whereIn('status', ['pending', 'under_review'])->latest()->get();
    }

    public function getAll()
    {
        return DeleteRequest::with('user')->latest()->get();
    }

    public function findById($id)
    {
        return DeleteRequest::with(['user', 'logs'])->findOrFail($id);
    }

    public function updateStatus(DeleteRequest $request, $status, $notes = null)
    {
        $request->update([
            'status' => $status,
            'notes' => $notes ?? $request->notes,
        ]);
        return $request;
    }

    public function delete(DeleteRequest $request)
    {
        return $request->delete();
    }
}
