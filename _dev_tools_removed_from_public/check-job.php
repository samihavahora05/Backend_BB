<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

$job = \App\Models\Job::find(34);
if ($job) {
    echo "Job 34 company_id: " . $job->company_id . "\n";
} else {
    echo "Job 34 not found\n";
}

$u = \App\Models\User::where('email', 'company@gmail.com')->first();
if ($u) {
    echo "Company user id: " . $u->id . "\n";
}
