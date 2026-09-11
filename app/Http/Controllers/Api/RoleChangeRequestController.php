<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleRequest;
use App\Services\OpportunityPermissionService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleChangeRequestController extends Controller
{
    /**
     * Submit a request to change role (e.g. Student -> Intern, Intern -> Jobseeker).
     * POST /api/role-change-requests
     */
    public function store(Request $request)
    {
        $request->validate([
            'requested_role' => 'required|string|in:student,intern,jobseeker,job-seeker',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $targetRole = strtolower(trim(str_replace('_', '-', $request->requested_role)));
        if ($targetRole === 'jobseeker') {
            $targetRole = 'job-seeker';
        }

        if (!OpportunityPermissionService::canRequestRoleChange($user, $targetRole)) {
            return response()->json([
                'success' => false,
                'message' => "You cannot request a transition to the {$targetRole} role from your current account type.",
            ], 422);
        }

        // Check if there is already an active pending request
        $pendingRequest = RoleRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            $pendingRole = ucfirst(str_replace('-', ' ', $pendingRequest->requested_role));
            return response()->json([
                'success' => false,
                'message' => "You already have a pending role change request to {$pendingRole}. Please wait for an administrator to review your request.",
                'data' => $pendingRequest
            ], 409);
        }

        $currentRole = $user->roles->first()?->name ?? 'student';
        $roleModel = Role::firstOrCreate([
            'name' => $targetRole,
            'guard_name' => 'web'
        ]);

        $roleRequest = RoleRequest::create([
            'user_id' => $user->id,
            'current_role' => $currentRole,
            'requested_role' => $targetRole,
            'requested_role_id' => $roleModel->id,
            'status' => 'pending',
            'reason' => $request->reason,
        ]);

        AuditLogService::log($user->id, 'role_change_requested', [
            'current_role' => $currentRole,
            'requested_role' => $targetRole,
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your role change request has been submitted successfully and is pending administrator review.',
            'data' => $roleRequest
        ], 201);
    }

    /**
     * Get current authenticated user role request status and history.
     * GET /api/role-change-requests/my-status
     */
    public function myStatus(Request $request)
    {
        $user = $request->user();
        $pending = RoleRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        $latest = RoleRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        $history = RoleRequest::where('user_id', $user->id)
            ->with('reviewer:id,first_name,last_name,email')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'has_pending' => !is_null($pending),
            'pending_request' => $pending,
            'latest_request' => $latest,
            'history' => $history,
        ]);
    }
}