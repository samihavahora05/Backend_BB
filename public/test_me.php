<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$user = \App\Models\User::find(1);
$token = $user->createToken('test')->plainTextToken;
echo "Token: $token\n";

$request = \Illuminate\Http\Request::create('/api/me', 'GET');
$request->headers->set('Authorization', "Bearer $token");
$request->headers->set('Accept', 'application/json');

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getContent() . "\n";
