<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "ID: " . $user->id . " | Email: " . $user->email . " | Roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "<br>";
}
