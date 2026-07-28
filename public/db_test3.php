<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$roles = \Spatie\Permission\Models\Role::all();
foreach($roles as $role) {
    echo "Role: {$role->name} | Guard: {$role->guard_name}<br>\n";
}
