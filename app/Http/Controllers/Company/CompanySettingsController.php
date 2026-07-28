<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanySetting;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CompanySettingsController extends Controller
{
    public function getSettings(Request $request)
    {
        $user = $request->user();
        if (!$user->company_id && !$user->hasRole('company')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If user is a company owner (role=company) and doesn't have company_id set in users table, 
        // they still own a profile in company_profiles.
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        if (!$companyId) {
            return response()->json(['message' => 'Company profile not found'], 404);
        }

        $settings = CompanySetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'notifications_config' => [
                    'newApps' => true,
                    'interviews' => true,
                    'weekly' => false
                ],
                'billing_preferences' => []
            ]
        );

        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'notifications_config' => 'nullable|array',
            'billing_preferences' => 'nullable|array',
        ]);

        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        if (!$companyId) {
            return response()->json(['message' => 'Company profile not found'], 404);
        }

        $settings = CompanySetting::firstOrCreate(['company_id' => $companyId]);

        if ($request->has('notifications_config')) {
            $settings->notifications_config = $request->notifications_config;
        }

        if ($request->has('billing_preferences')) {
            $settings->billing_preferences = $request->billing_preferences;
        }

        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $settings
        ]);
    }

    public function getTeamMembers(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        if (!$companyId) {
            return response()->json(['message' => 'Company profile not found'], 404);
        }

        // Include the owner (role=company) and team members (company_id=...)
        $members = User::where('id', clone $user->id) // The owner (current user might be owner)
            ->orWhere('company_id', $companyId)
            ->with('roles')
            ->get();
            
        // If the current user is a team member, the owner can be found via companyProfile user_id
        $companyProfile = \App\Models\CompanyProfile::find($companyId);
        if ($companyProfile) {
            $owner = User::find($companyProfile->user_id);
            if ($owner && !$members->contains('id', $owner->id)) {
                $owner->load('roles');
                $members->push($owner);
            }
        }

        $invitations = CompanyInvitation::where('company_id', $companyId)->get();

        return response()->json([
            'members' => $members,
            'invitations' => $invitations
        ]);
    }

    public function inviteTeamMember(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string'
        ]);

        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        // Check if user already exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'User with this email already exists on the platform.'], 400);
        }

        $token = Str::random(40);
        
        $invitation = CompanyInvitation::create([
            'company_id' => $companyId,
            'email' => $request->email,
            'role' => $request->role,
            'token' => $token,
            'expires_at' => now()->addDays(7)
        ]);

        // In a real app, send email here. For now we just log it or rely on the frontend flow.
        Log::info("Team member invitation created for {$request->email} with token {$token}");

        return response()->json([
            'message' => 'Invitation sent successfully',
            'invitation' => $invitation
        ]);
    }

    public function removeTeamMember(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companyProfile?->id;

        $member = User::where('id', $id)->where('company_id', $companyId)->first();
        
        if (!$member) {
            return response()->json(['message' => 'Team member not found or you do not have permission'], 404);
        }

        $member->company_id = null;
        // Optionally remove role
        $member->save();

        return response()->json(['message' => 'Team member removed successfully']);
    }
}
