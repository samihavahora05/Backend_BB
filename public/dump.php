<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $tickets = \Illuminate\Support\Facades\DB::table('support_tickets')->get();
    $companies = \Illuminate\Support\Facades\DB::table('company_profiles')->get();
    
    echo json_encode([
        'tickets' => $tickets,
        'companies' => $companies
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    echo $e->getMessage();
}
