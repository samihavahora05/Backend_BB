<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$companyId = 547;
$jobIds = \App\Models\Job::where('company_id', $companyId)->pluck('id');

$applications = \App\Models\JobApplication::whereIn('job_id', $jobIds)
    ->with(['job', 'user.jobSeekerProfile', 'user.studentProfile'])
    ->latest()
    ->get()
    ->map(function($app) {
        return [
            'id' => $app->id,
            'jobId' => $app->job_id,
            'jobTitle' => $app->job ? $app->job->title : 'Unknown Job',
            'applicantName' => $app->user ? $app->user->name : 'Unknown',
            'email' => $app->user ? $app->user->email : '',
            'status' => $app->status,
        ];
    });

echo "Applications count: " . $applications->count() . "\n";
echo "Data: " . json_encode($applications) . "\n";
