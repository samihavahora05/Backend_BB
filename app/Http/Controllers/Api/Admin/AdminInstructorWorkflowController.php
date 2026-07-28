<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\InstructorService;
use Illuminate\Http\Request;

class AdminInstructorWorkflowController extends Controller
{
    protected $service;

    public function __construct(InstructorService $service)
    {
        $this->service = $service;
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorize('manage experts');
        $request->validate([
            'status' => 'required|in:approved,rejected,suspended,pending',
            'notes' => 'nullable|string'
        ]);

        $profile = $this->service->updateApprovalStatus($id, $request->status, $request->user()->id, $request->notes);
        
        return response()->json(['success' => true, 'message' => "Instructor status updated to {$request->status}"]);
    }

    public function resetPassword(Request $request, $id)
    {
        $this->authorize('manage experts');
        $newPassword = $this->service->resetPassword($id, $request->user()->id);
        
        // In a real scenario, this wouldn't be returned but emailed.
        // For demonstration, we return it.
        return response()->json([
            'success' => true, 
            'message' => 'Password reset successfully. Credentials sent.',
            'new_password' => $newPassword
        ]);
    }
}
