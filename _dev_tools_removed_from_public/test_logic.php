<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(10); // User ID 10 is the student
$appliedInternshipIds = \App\Models\InternshipApplication::where('user_id', $user->id)
    ->pluck('internship_id')
    ->toArray();

echo "User ID: " . $user->id . "\n";
echo "Applied IDs: " . implode(", ", $appliedInternshipIds) . "\n";

$i_id = 1; // Assuming internship ID 1
echo "Has applied for ID $i_id: " . (in_array($i_id, $appliedInternshipIds) ? 'true' : 'false') . "\n";
