<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Route;

Route::get('/api/test-auth', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    if (!$user) {
        return response()->json(['error' => 'Not authenticated']);
    }
    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'roles' => $user->roles->toArray(),
        'has_company_role' => $user->hasRole('company'),
        'has_company_role_sanctum' => $user->hasRole('company', 'sanctum'),
        'has_any_role_company' => $user->hasAnyRole(['company']),
    ]);
})->middleware('auth:sanctum');

