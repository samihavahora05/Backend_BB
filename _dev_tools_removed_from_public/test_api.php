<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/admin/approvals', 'GET');
// simulate admin login
$admin = \App\Models\User::where('email', 'admin@blueboxx.in')->first();
$app->make('auth')->guard('sanctum')->setUser($admin);
$request->setUserResolver(function () use ($admin) { return $admin; });

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
