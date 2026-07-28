<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

// Create admin role if not exists
$role = Role::firstOrCreate(['name' => 'admin']);

// Create admin user
$admin = User::firstOrCreate(
    ['email' => 'admin@blueboxx.in'],
    [
        'first_name' => 'System',
        'last_name' => 'Admin',
        'password' => Hash::make('password'),
        'status' => 'active',
        'email_verified_at' => now(),
    ]
);

$admin->assignRole('admin');

echo json_encode([
    'success' => true,
    'message' => 'Admin user created/verified successfully!',
    'user' => $admin->only('id', 'email', 'first_name', 'last_name')
]);
