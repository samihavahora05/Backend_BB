<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('config:clear');

$guards = config('auth.guards');
$providers = config('auth.providers');
$model = config('auth.providers.users.model');
echo "auth.guards: " . json_encode($guards) . "\n";
echo "auth.providers: " . json_encode($providers) . "\n";
echo "auth.providers.users.model: " . var_export($model, true) . "\n";
echo "getModelForGuard('web'): " . var_export(getModelForGuard('web'), true) . "\n";
echo "getModelForGuard('sanctum'): " . var_export(getModelForGuard('sanctum'), true) . "\n";
