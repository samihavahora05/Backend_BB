<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeleteRequestService;
use App\Models\DeleteRequest;
use Illuminate\Http\Request;

class DeleteRequestController extends Controller
{
    protected $deleteRequestService;

    public function __construct(DeleteRequestService $deleteRequestService)
    {
        $this->deleteRequestService = $deleteRequestService;
    }

    public function index()
    {
        return response()->json($this->deleteRequestService->getPendingRequests());
    }

    public function approve(Request $request, DeleteRequest $deleteRequest)
    {
        $this->deleteRequestService->approveRequest($deleteRequest);
        return response()->json(['message' => 'Delete request approved and user data purged.']);
    }

    public function reject(Request $request, DeleteRequest $deleteRequest)
    {
        $validated = $request->validate([
            'notes' => 'required|string'
        ]);

        $this->deleteRequestService->rejectRequest($deleteRequest, $validated['notes']);
        return response()->json(['message' => 'Delete request rejected.']);
    }
}
