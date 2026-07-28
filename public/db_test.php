<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$migrations = DB::table('migrations')->get();
echo "Migrations in DB:\n";
foreach($migrations as $m) {
    echo $m->migration . "\n";
}

echo "\nColumns in jobs:\n";
$columns = DB::select('SHOW COLUMNS FROM jobs');
foreach($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}
