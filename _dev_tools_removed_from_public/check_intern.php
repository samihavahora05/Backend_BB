<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$interns = \App\Models\User::role('intern')->orderBy('id', 'desc')->take(5)->get();
foreach ($interns as $intern) {
    echo "ID: {$intern->id}, Email: {$intern->email}, Status: {$intern->status}\n";
}
