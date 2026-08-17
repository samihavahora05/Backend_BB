<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Find the admin user
$user = \App\Models\User::where('email', 'admin@blueboxx.in')->first();

if (!$user) {
    die("Admin user not found.");
}

// Log in as the user on sanctum guard by setting the actingAs
// Wait, we can generate a token and use it.
$token = $user->createToken('test')->plainTextToken;

// Simulate API Request
$request = Illuminate\Http\Request::create('/api/admin/approvals', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "<br>\n";
echo "Content: " . $response->getContent() . "<br>\n";

// Check the user's roles
echo "User ID: " . $user->id . "<br>\n";
echo "User has admin role on web guard? " . ($user->hasRole('admin', 'web') ? 'Yes' : 'No') . "<br>\n";
echo "User has admin role on sanctum guard? " . ($user->hasRole('admin', 'sanctum') ? 'Yes' : 'No') . "<br>\n";
