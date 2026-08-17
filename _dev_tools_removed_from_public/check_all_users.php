<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $users = \App\Models\User::all();
    foreach ($users as $u) {
        echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, DB Role: {$u->role}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
