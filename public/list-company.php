<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

echo "Roles for Company:";
$users = \App\Models\User::where('email', 'like', '%company%')->get();
foreach ($users as $u) {
    echo $u->email . " - " . implode(',', $u->roles->pluck('name')->toArray()) . "<br>";
}
