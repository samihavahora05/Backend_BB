<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$admin = \App\Models\User::where('email', 'admin@blueboxx.in')->first();
if (!$admin) {
    $admin = \App\Models\User::first();
}

if ($admin) {
    echo "Fixing admin roles for user: {$admin->email}\n";
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
    $admin->assignRole($role);
    
    $roleWeb = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin->assignRole($roleWeb);
    
    echo "Super admin roles assigned for both guards.\n";
    
    // Also remove the student role if they have it
    $studentRole = \Spatie\Permission\Models\Role::where('name', 'student')->where('guard_name', 'sanctum')->first();
    if ($studentRole && $admin->hasRole($studentRole)) {
        $admin->removeRole($studentRole);
        echo "Removed student role from admin.\n";
    }
}
