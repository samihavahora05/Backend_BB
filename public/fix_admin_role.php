<?php

/**
 * Emergency Admin Role Fix Script
 * Run this from: http://127.0.0.1:8000/fix_admin_role.php
 *
 * This script assigns the super_admin role to the admin user.
 * DELETE this file after running it.
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

$output = [];

// 1. Ensure roles exist
$roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college'];
foreach ($roles as $roleName) {
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $output[] = "✅ Role '{$roleName}' exists (ID: {$role->id})";
}

// 2. Find ALL admin users (by email patterns)
$adminEmails = ['admin@blueboxx.in', 'admin@blueboxx.com', 'admin@example.com'];
$adminUsers = User::whereIn('email', $adminEmails)->get();

if ($adminUsers->isEmpty()) {
    // Try to find the first user who doesn't have student role
    $adminUsers = User::whereDoesntHave('roles', function($q) {
        $q->where('name', 'student');
    })->get();
    $output[] = "⚠️  No exact admin email match found. Looking at non-student users: " . $adminUsers->count() . " found.";
}

if ($adminUsers->isEmpty()) {
    // Last resort: find user with ID 1
    $adminUsers = User::where('id', 1)->get();
    $output[] = "⚠️  Using User ID=1 as fallback.";
}

foreach ($adminUsers as $user) {
    // Remove old roles and assign fresh super_admin
    $user->syncRoles(['super_admin']);
    $output[] = "✅ Assigned 'super_admin' to: {$user->email} (ID: {$user->id})";
}

// 3. List all users with their roles
$output[] = "\n--- All Users & Their Roles ---";
$users = User::with('roles')->get();
foreach ($users as $user) {
    $roleList = $user->roles->pluck('name')->join(', ') ?: 'NO ROLE';
    $output[] = "  [{$user->id}] {$user->email} → {$roleList}";
}

// Output
header('Content-Type: text/plain');
echo implode("\n", $output);
echo "\n\n✅ DONE. Please DELETE this file (public/fix_admin_role.php) now.\n";
