<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo json_encode([
    'expert_profiles' => Illuminate\Support\Facades\Schema::getColumnListing('expert_profiles'),
    'users' => Illuminate\Support\Facades\Schema::getColumnListing('users'),
    'testimonials' => Illuminate\Support\Facades\Schema::getColumnListing('testimonials'),
    'faqs' => Illuminate\Support\Facades\Schema::getColumnListing('faqs')
]);
