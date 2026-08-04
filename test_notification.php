<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'student');
})->first();

if (!$user) {
    echo "No student found.\n";
    exit;
}

// Ensure the user has a notification
$user->notify(new \App\Notifications\PlatformNotification(
    'Test Notification',
    'This is a test notification for student.',
    'system_alert',
    ['url' => '/student/dashboard']
));

$notifications = $user->notifications()->take(5)->get();

echo "User ID: " . $user->id . "\n";
echo "Notifications JSON: \n";
echo json_encode($notifications, JSON_PRETTY_PRINT) . "\n";
