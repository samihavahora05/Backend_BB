<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$notifications = \DB::table('notifications')->count();
$studentNotifications = \DB::table('student_notifications')->count();

echo "Count in notifications: $notifications<br>\n";
echo "Count in student_notifications: $studentNotifications<br>\n";
