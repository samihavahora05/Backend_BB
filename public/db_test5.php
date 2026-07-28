<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Does notifications table exist? " . (\Schema::hasTable('notifications') ? 'Yes' : 'No') . "<br>\n";
echo "Does student_notifications table exist? " . (\Schema::hasTable('student_notifications') ? 'Yes' : 'No') . "<br>\n";
