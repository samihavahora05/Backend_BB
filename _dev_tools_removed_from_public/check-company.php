<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$u = \App\Models\User::where('email', 'company@gmail.com')->first();
if ($u) {
    echo "User found: " . $u->email . " | Roles: " . implode(',', $u->roles->pluck('name')->toArray());
} else {
    echo "User not found";
}
