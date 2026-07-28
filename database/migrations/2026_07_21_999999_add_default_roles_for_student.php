<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // First or create roles to prevent RoleDoesNotExist errors
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'company', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        // We typically don't remove these core roles on rollback
    }
};
