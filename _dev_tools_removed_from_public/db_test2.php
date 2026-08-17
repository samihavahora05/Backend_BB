<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$users = \App\Models\User::with('roles')->get();
foreach($users as $user) {
    echo "ID: {$user->id} | Email: {$user->email} | Status: {$user->status} | Roles: " . $user->roles->pluck('name')->join(', ') . "<br>\n";
}
