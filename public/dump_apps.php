<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$apps = Illuminate\Support\Facades\DB::select('SELECT * FROM internship_applications');
file_put_contents('db_dump.json', json_encode($apps, JSON_PRETTY_PRINT));
echo "Done";
