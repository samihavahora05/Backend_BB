<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleRepository
{
    public function getAllWithUserCounts()
    {
        return Role::withCount('users')->with(['permissions', 'users'])->get();
    }

    public function findById($id)
    {
        return Role::with(['permissions', 'users'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => 'web',
            'status' => $data['status'] ?? 'Active',
            'created_by' => auth()->id(),
        ]);
    }

    public function update(Role $role, array $data)
    {
        $role->update($data);
        return $role;
    }

    public function delete(Role $role)
    {
        return $role->delete();
    }

    public function syncPermissions(Role $role, array $permissions)
    {
        // permissions array should contain permission names
        // Ensure permissions exist in DB before syncing
        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }
        
        $role->syncPermissions($permissions);
        return $role->load('permissions');
    }
}
