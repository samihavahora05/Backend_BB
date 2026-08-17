<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $u = \App\Models\User::find(9);
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}\n";
    echo "Spatie Roles: " . implode(', ', $u->roles->pluck('name')->toArray()) . "\n";
    echo "Spatie Permissions: " . implode(', ', $u->permissions->pluck('name')->toArray()) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
