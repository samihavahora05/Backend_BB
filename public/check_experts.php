<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$experts = \App\Models\ExpertProfile::with('user')->get();
echo "Total Experts: " . $experts->count() . "\n";
foreach ($experts as $e) {
    echo "ID: {$e->id}, UserID: {$e->user_id}, Name: " . ($e->user ? $e->user->name : 'NoUser') . "\n";
    echo "   is_available: " . ($e->is_available ? 'true' : 'false') . "\n";
    echo "   is_verified: " . ($e->is_verified ? 'true' : 'false') . "\n";
    echo "   user status: " . ($e->user ? $e->user->status : 'null') . "\n";
}
