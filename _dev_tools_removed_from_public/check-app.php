<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$apps = \App\Models\JobApplication::where('job_id', 34)->get();
echo "Total apps for job 34: " . $apps->count() . "\n";
foreach($apps as $app) {
    echo "App ID: " . $app->id . ", user_id: " . $app->user_id . "\n";
}
