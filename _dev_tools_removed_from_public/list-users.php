<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;

$users = User::all();
foreach ($users as $u) {
    echo "Email: " . $u->email . " | Roles: " . implode(',', $u->roles->pluck('name')->toArray()) . "<br>\n";
}
