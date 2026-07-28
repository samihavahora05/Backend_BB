<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;
use App\Models\RoleActivityLog;
use Illuminate\Support\Facades\DB;

class RoleService
{
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getAllRoles()
    {
        return $this->roleRepository->getAllWithUserCounts();
    }

    public function createRole(array $data)
    {
        return DB::transaction(function () use ($data) {
            $role = $this->roleRepository->create($data);
            
            $this->logActivity($role->id, 'created', null, $data);

            if (!empty($data['permissions'])) {
                $role = $this->roleRepository->syncPermissions($role, $data['permissions']);
                $this->logActivity($role->id, 'permissions_synced', null, ['permissions' => $data['permissions']]);
            }

            return $role;
        });
    }

    public function updateRole(Role $role, array $data)
    {
        if (in_array($role->name, ['super_admin', 'admin']) && isset($data['name']) && $data['name'] !== $role->name) {
            throw new \Exception('Cannot rename core system roles.');
        }

        $oldValues = $role->toArray();

        return DB::transaction(function () use ($role, $data, $oldValues) {
            $role = $this->roleRepository->update($role, $data);
            
            $this->logActivity($role->id, 'updated', $oldValues, $role->toArray());

            if (isset($data['permissions'])) {
                $oldPerms = $role->permissions->pluck('name')->toArray();
                $role = $this->roleRepository->syncPermissions($role, $data['permissions']);
                $this->logActivity($role->id, 'permissions_synced', ['permissions' => $oldPerms], ['permissions' => $data['permissions']]);
            }

            return $role;
        });
    }

    public function cloneRole(Role $role, array $data)
    {
        return DB::transaction(function () use ($role, $data) {
            $newRoleData = [
                'name' => $data['name'] ?? $role->name . ' (Copy)',
                'description' => $data['description'] ?? $role->description,
                'status' => 'Active',
            ];

            $newRole = $this->roleRepository->create($newRoleData);
            
            $permissions = $role->permissions->pluck('name')->toArray();
            $newRole = $this->roleRepository->syncPermissions($newRole, $permissions);

            $this->logActivity($newRole->id, 'cloned', ['from_role_id' => $role->id], $newRoleData);

            return $newRole;
        });
    }

    public function deleteRole(Role $role)
    {
        if (in_array($role->name, ['super_admin', 'admin'])) {
            throw new \Exception('Cannot delete core system roles.');
        }

        if ($role->users()->count() > 0) {
            throw new \Exception('Cannot delete role with active users.');
        }

        $roleId = $role->id;
        return DB::transaction(function () use ($role, $roleId) {
            RoleActivityLog::where('role_id', $roleId)->delete();
            $this->roleRepository->delete($role);
            return true;
        });
    }

    public function assignUsers(Role $role, array $userIds)
    {
        // For simplified assignment, sync users
        $role->users()->syncWithoutDetaching($userIds);
        $this->logActivity($role->id, 'users_assigned', null, ['user_ids' => $userIds]);
        return true;
    }

    public function removeUser(Role $role, $userId)
    {
        $role->users()->detach($userId);
        $this->logActivity($role->id, 'user_removed', ['user_id' => $userId], null);
        return true;
    }

    protected function logActivity($roleId, $action, $oldValues = null, $newValues = null)
    {
        RoleActivityLog::create([
            'role_id' => $roleId,
            'admin_id' => auth()->id() ?? 1, // Fallback for seeding/testing
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
