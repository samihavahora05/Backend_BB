<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleRequest;
use Illuminate\Http\Request;

class AdminRoleRequestController extends Controller
{
    public function index()
    {
        $requests = RoleRequest::with(['user', 'requestedRole'])->latest()->get();
        return response()->json($requests);
    }

    public function approve($id)
    {
        $roleRequest = RoleRequest::findOrFail($id);
        
        if ($roleRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be approved.'], 400);
        }

        // Assign the role to the user
        $user = $roleRequest->user;
        $role = $roleRequest->requestedRole;
        
        if ($user && $role) {
            // Usually, users have one main role in this system, so we sync it. 
            // Or we assign it. Let's sync to replace their current role.
            $user->syncRoles([$role->name]);
            
            $roleRequest->status = 'approved';
            $roleRequest->save();

            return response()->json(['message' => 'Role request approved and role assigned successfully.']);
        }

        return response()->json(['message' => 'User or Role not found.'], 404);
    }

    public function reject(Request $req, $id)
    {
        $validated = $req->validate([
            'notes' => 'required|string',
        ]);

        $roleRequest = RoleRequest::findOrFail($id);
        
        if ($roleRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be rejected.'], 400);
        }

        $roleRequest->status = 'rejected';
        $roleRequest->notes = $validated['notes'];
        $roleRequest->save();

        return response()->json(['message' => 'Role request rejected successfully.']);
    }
}
