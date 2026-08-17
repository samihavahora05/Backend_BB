<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $companies = \Illuminate\Support\Facades\DB::table('users')->select('id', 'email')->get()->toArray();
    echo "<pre>"; print_r($companies); echo "</pre>";
} catch (\Exception $e) {
    echo $e->getMessage();
}
