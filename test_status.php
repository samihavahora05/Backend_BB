<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Jobs statuses: " . json_encode(\App\Models\Job::pluck('status')->unique()) . "\n";
echo "Courses statuses: " . json_encode(\App\Models\Course::pluck('status')->unique()) . "\n";
echo "Internships statuses: " . json_encode(\App\Models\Internship::pluck('status')->unique()) . "\n";
