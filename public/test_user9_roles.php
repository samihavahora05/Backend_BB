<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(9);
echo "User 9 roles:\n";
foreach ($user->roles as $role) {
    echo " - {$role->name} (guard: {$role->guard_name})\n";
}
echo "\nHas 'admin' role? " . ($user->hasRole('admin') ? 'YES' : 'NO') . "\n";
echo "Has 'super_admin' role? " . ($user->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
echo "HasAnyRole 'super_admin|admin'? " . ($user->hasAnyRole(['super_admin', 'admin']) ? 'YES' : 'NO') . "\n";

echo "\nNow testing with Sanctum guard override (Spatie behavior during auth:sanctum requests)\n";
try {
    app('auth')->shouldUse('sanctum'); // Simulate Sanctum guard being active
    echo "HasAnyRole via sanctum? " . ($user->hasAnyRole(['super_admin', 'admin']) ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
