<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $users = \App\Models\User::all();
    foreach ($users as $u) {
        if (str_contains(strtolower($u->role), 'admin')) {
            echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, DB Role: {$u->role}\n";
            try {
                echo "Spatie Roles: " . implode(', ', $u->roles->pluck('name')->toArray()) . "\n";
            } catch (\Exception $e) {
                echo "No Spatie roles available\n";
            }
            echo "---\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
