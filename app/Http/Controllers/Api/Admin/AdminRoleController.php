<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use App\Models\RoleActivityLog;

class AdminRoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role = $this->roleService->createRole($validated);
        return response()->json(['message' => 'Role created successfully', 'data' => $role], 201);
    }

    public function show($id)
    {
        $role = Role::with('permissions', 'users')->findOrFail($id);
        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        try {
            $role = $this->roleService->updateRole($role, $validated);
            return response()->json(['message' => 'Role updated successfully', 'data' => $role]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        try {
            $this->roleService->deleteRole($role);
            return response()->json(['message' => 'Role deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function clone(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
        ]);

        $newRole = $this->roleService->cloneRole($role, $validated);
        return response()->json(['message' => 'Role cloned successfully', 'data' => $newRole], 201);
    }

    public function assignUsers(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $this->roleService->assignUsers($role, $validated['user_ids']);
        return response()->json(['message' => 'Users assigned successfully']);
    }

    public function removeUser($id, $userId)
    {
        $role = Role::findOrFail($id);
        $this->roleService->removeUser($role, $userId);
        return response()->json(['message' => 'User removed successfully']);
    }

    public function getAuditLogs(Request $request)
    {
        $id = $request->query('id');
        $query = RoleActivityLog::with('admin')->latest();
        if ($id) {
            $query->where('role_id', $id);
        }
        return response()->json($query->get());
    }

    public function exportRoles()
    {
        $roles = $this->roleService->getAllRoles();
        
        $headers = ['ID', 'Name', 'Description', 'Status', 'Users Count', 'Created At'];
        
        $callback = function() use ($roles, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($roles as $role) {
                fputcsv($file, [
                    $role->id,
                    $role->name,
                    $role->description,
                    $role->status,
                    $role->users_count,
                    $role->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'roles_export_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function exportAuditLogs(Request $request)
    {
        $id = $request->query('id');
        $query = RoleActivityLog::with('admin')->latest();
        if ($id) {
            $query->where('role_id', $id);
        }
        $logs = $query->get();

        $headers = ['ID', 'Date', 'Admin', 'Action', 'IP Address'];
        
        $callback = function() use ($logs, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($logs as $log) {
                $adminName = $log->admin ? $log->admin->first_name . ' ' . $log->admin->last_name : 'System';
                fputcsv($file, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $adminName,
                    strtoupper($log->action),
                    $log->ip_address,
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'role_audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function importRoles(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        
        try {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle, 1000, ',');
            
            // Expected headers: Name, Description, Permissions
            // Permissions should be comma separated like 'view_users,edit_settings'
            
            $imported = 0;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($header) == count($row)) {
                    $data = array_combine($header, $row);
                    
                    if (isset($data['Name'])) {
                        $roleData = [
                            'name' => $data['Name'],
                            'description' => $data['Description'] ?? null,
                            'status' => 'Active'
                        ];

                        $role = \Spatie\Permission\Models\Role::firstOrCreate(
                            ['name' => $roleData['name'], 'guard_name' => 'web'],
                            ['description' => $roleData['description'], 'status' => 'Active', 'created_by' => auth()->id() ?? 1]
                        );

                        if (isset($data['Permissions']) && !empty($data['Permissions'])) {
                            $permissions = array_map('trim', explode(';', $data['Permissions']));
                            $this->roleService->updateRole($role, ['permissions' => $permissions]);
                        }
                        
                        $imported++;
                    }
                }
            }
            fclose($handle);

            return response()->json(['message' => "$imported roles imported successfully."]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to import roles: ' . $e->getMessage()], 400);
        }
    }
}
