<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();
use App\Models\User;
use Spatie\Permission\Models\Role;

$user = clone User::find(1);
echo "User ID 1 roles (web): " . json_encode($user->roles()->get()->pluck('name')) . "\n";

// test hasAnyRole with sanctum guard
$user->setDefaultGuardName('sanctum');
try {
    echo "Has super_admin|admin via sanctum? " . ($user->hasAnyRole(['super_admin', 'admin']) ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Assign sanctum roles to User 1 just to be safe
$roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college'];
foreach ($roles as $roleName) {
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
}
$user->assignRole('super_admin');
echo "Assigned super_admin with sanctum guard to User 1.\n";
