<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleRequest;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminRoleRequestController extends Controller
{
    /**
     * List all role change requests for Admin.
     * GET /api/admin/role-requests
     */
    public function index()
    {
        $requests = RoleRequest::with([
            'user:id,first_name,last_name,email,phone,status',
            'reviewer:id,first_name,last_name,email',
            'requestedRole'
        ])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Approve role change request.
     * POST /api/admin/role-requests/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $roleRequest = RoleRequest::with('user')->findOrFail($id);
        
        if ($roleRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending role requests can be approved.'
            ], 400);
        }

        $user = $roleRequest->user;
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Applicant user account not found.'
            ], 404);
        }

        $targetRole = $roleRequest->requested_role;
        if (!$targetRole && $roleRequest->requestedRole) {
            $targetRole = $roleRequest->requestedRole->name;
        }

        $normalizedTarget = strtolower(trim(str_replace('_', '-', $targetRole)));
        if ($normalizedTarget === 'jobseeker') {
            $normalizedTarget = 'job-seeker';
        }

        // Ensure role exists in Spatie
        Role::firstOrCreate(['name' => $normalizedTarget, 'guard_name' => 'web']);

        // 1. Sync role (replaces current applicant role)
        $oldRole = $user->roles->first()?->name ?? $roleRequest->current_role ?? 'student';
        $user->syncRoles([$normalizedTarget]);

        // 2. Auto-provision profile if needed
        match ($normalizedTarget) {
            'student' => $user->studentProfile()->firstOrCreate([]),
            'intern' => $user->internProfile()->firstOrCreate([]),
            'job-seeker', 'jobseeker' => $user->jobSeekerProfile()->firstOrCreate([]),
            default => null,
        };

        // 3. Update Request status
        $roleRequest->status = 'approved';
        $roleRequest->reviewed_by = auth()->id();
        $roleRequest->reviewed_at = now();
        $roleRequest->save();

        // 4. Audit Log
        AuditLogService::log($user->id, 'role_change_approved', [
            'old_role' => $oldRole,
            'new_role' => $normalizedTarget,
            'approved_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Role request approved successfully. {$user->name} is now assigned as {$normalizedTarget}.",
            'data' => $roleRequest
        ]);
    }

    /**
     * Reject role change request with mandatory reason.
     * POST /api/admin/role-requests/{id}/reject
     */
    public function reject(Request $req, $id)
    {
        $validated = $req->validate([
            'rejection_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reason = $validated['rejection_reason'] ?? $validated['notes'] ?? null;
        if (empty(trim($reason))) {
            return response()->json([
                'success' => false,
                'message' => 'A rejection reason is required to reject a role change request.'
            ], 422);
        }

        $roleRequest = RoleRequest::with('user')->findOrFail($id);
        
        if ($roleRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending role requests can be rejected.'
            ], 400);
        }

        $roleRequest->status = 'rejected';
        $roleRequest->rejection_reason = $reason;
        $roleRequest->notes = $reason;
        $roleRequest->reviewed_by = auth()->id();
        $roleRequest->reviewed_at = now();
        $roleRequest->save();

        AuditLogService::log($roleRequest->user_id, 'role_change_rejected', [
            'current_role' => $roleRequest->current_role,
            'requested_role' => $roleRequest->requested_role,
            'rejection_reason' => $reason,
            'rejected_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role request rejected successfully.',
            'data' => $roleRequest
        ]);
    }
}