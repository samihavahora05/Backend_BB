<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\DB::statement('select 1');
    echo "DB Connected.\n";
    $status = $kernel->call('migrate', ['--force' => true]);
    echo $kernel->output();
} catch (\Exception $e) {
    echo $e->getMessage();
}
