<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();
use App\Models\User;

$user = clone User::find(1);
echo "User 1 roles (web): " . json_encode($user->roles()->get()->pluck('name')) . "\n";

// test hasAnyRole with sanctum guard
$user->setDefaultGuardName('sanctum');
try {
    echo "Has super_admin|admin via sanctum? " . ($user->hasAnyRole(['super_admin', 'admin']) ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
