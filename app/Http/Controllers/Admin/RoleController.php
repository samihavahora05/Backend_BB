<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\RoleActivityLog;
use App\Models\User;

class RoleController extends Controller
{
    protected $roleService;
    protected $roleRepository;

    public function __construct(RoleService $roleService, RoleRepository $roleRepository)
    {
        $this->roleService = $roleService;
        $this->roleRepository = $roleRepository;
    }

    public function index()
    {
        return response()->json($this->roleService->getAllRoles());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        $role = $this->roleService->createRole($validated);
        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:Active,Inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        $updatedRole = $this->roleService->updateRole($role, $validated);
        return response()->json(['message' => 'Role updated successfully', 'role' => $updatedRole]);
    }

    public function destroy(Role $role)
    {
        $this->roleService->deleteRole($role);
        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function clone(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|unique:roles,name',
            'description' => 'nullable|string'
        ]);

        $clonedRole = $this->roleService->cloneRole($role, $validated);
        return response()->json(['message' => 'Role cloned successfully', 'role' => $clonedRole], 201);
    }

    public function assignUsers(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $this->roleService->assignUsers($role, $validated['user_ids']);
        return response()->json(['message' => 'Users assigned to role successfully']);
    }

    public function removeUser(Role $role, $userId)
    {
        $this->roleService->removeUser($role, $userId);
        return response()->json(['message' => 'User removed from role successfully']);
    }

    public function auditLogs(Role $role)
    {
        $logs = RoleActivityLog::with('admin')->where('role_id', $role->id)->latest()->get();
        return response()->json($logs);
    }

    public function allAuditLogs()
    {
        $logs = RoleActivityLog::with(['admin', 'role'])->latest()->get();
        return response()->json($logs);
    }
}
