<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migrations = \Illuminate\Support\Facades\DB::table('migrations')->orderBy('id', 'desc')->limit(5)->get();
echo "<pre>";
print_r($migrations);
echo "</pre>";
