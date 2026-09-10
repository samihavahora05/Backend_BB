<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Services\AuditLogService;

class ApprovalController extends Controller
{
    /**
     * Get a list of pending approval users, optionally filtered by role
     */
    public function index(Request $request)
    {
        $query = User::with([
            'roles',
            'expertProfile',
            'companyProfile',
            'collegeProfile',
            'studentProfile',
            'internProfile',
            'jobSeekerProfile'
        ])
        ->where(function ($q) {
            $q->where('status', 'pending_approval')
              ->orWhere('account_status', 'pending_admin_approval');
        });

        if ($request->has('role') && !empty($request->role)) {
            $query->role($request->role);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get full details of an applicant for admin review.
     */
    public function show(Request $request, $id)
    {
        $user = User::with([
            'roles',
            'expertProfile',
            'companyProfile',
            'collegeProfile',
            'studentProfile',
            'internProfile',
            'jobSeekerProfile',
            'expertSkills',
            'expertDocuments',
            'education'
        ])->findOrFail($id);

        AuditLogService::log(auth()->id(), 'admin_reviewed_account', [
            'applicant_id' => $user->id,
            'applicant_email' => $user->email,
            'role' => $user->roles->first()?->name
        ]);

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Approve a user
     */
    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Validation: Email verification is required before admin approval!
        if (!$user->isEmailVerified()) {
            return response()->json([
                'message' => 'Email verification is required before approval. The applicant has not verified their email address yet.',
                'code' => 'EMAIL_NOT_VERIFIED'
            ], 422);
        }

        $user->update([
            'admin_approved' => true,
            'admin_approved_at' => now(),
            'approved_by' => auth()->id(),
            'account_status' => 'active',
            'status' => 'active',
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        // If Expert, activate profile
        if ($user->hasRole('expert') && $user->expertProfile) {
            $user->expertProfile->update(['is_verified' => true]);
        }
        
        AuditLogService::log(auth()->id(), 'admin_approved_account', [
            'applicant_id' => $user->id,
            'applicant_email' => $user->email,
            'role' => $user->roles->first()?->name
        ]);

        Log::info("Admin ID [" . auth()->id() . "] approved user ID: {$user->id}");

        // Send approval notification email
        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (\Throwable $e) {
            Log::error("Failed to send approval email to user {$user->id}: " . $e->getMessage());
        }

        try {
            $user->notify(new \App\Notifications\AccountStatusUpdated('active', 'Your account has been approved by the administrator. You can now log in.'));
        } catch (\Throwable $notifEx) {
            // Ignore notification error if any
        }

        return response()->json([
            'success' => true,
            'message' => 'User approved successfully. Notification email dispatched.',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Reject a user registration with mandatory reason
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $user = User::findOrFail($id);

        $reason = trim($request->input('reason'));

        $user->update([
            'admin_approved' => false,
            'account_status' => 'rejected',
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        $user->tokens()->delete(); // Revoke any active tokens
        
        AuditLogService::log(auth()->id(), 'admin_rejected_account', [
            'applicant_id' => $user->id,
            'applicant_email' => $user->email,
            'reason' => $reason
        ]);

        Log::info("Admin ID [" . auth()->id() . "] rejected user ID: {$user->id} with reason: {$reason}");

        // Send rejection email
        try {
            Mail::to($user->email)->send(new AccountRejectedMail($user, $reason));
        } catch (\Throwable $e) {
            Log::error("Failed to send rejection email to user {$user->id}: " . $e->getMessage());
        }

        try {
            $user->notify(new \App\Notifications\AccountStatusUpdated('rejected', 'Your account registration was not approved. Reason: ' . $reason));
        } catch (\Throwable $notifEx) {
            // Ignore notification error if any
        }

        return response()->json([
            'success' => true,
            'message' => 'User application rejected and notification email sent.',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Suspend an active user
     */
    public function suspend(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'account_status' => 'suspended',
            'status' => 'suspended'
        ]);
        
        $user->tokens()->delete(); // Force logout
        
        AuditLogService::log(auth()->id(), 'account_suspended', [
            'applicant_id' => $user->id,
            'applicant_email' => $user->email
        ]);

        Log::info("Admin ID [" . auth()->id() . "] suspended user ID: {$user->id}");

        try {
            $user->notify(new \App\Notifications\AccountStatusUpdated('suspended', 'Your account has been suspended by the administrator. Please contact support.'));
        } catch (\Throwable $notifEx) {
            // Ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully.',
            'user' => $user->load('roles')
        ]);
    }
}