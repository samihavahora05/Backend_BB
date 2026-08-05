<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Direct DB query
$courses = \App\Models\Course::select('id', 'title', 'status', 'is_archived', 'is_published')->get();

echo json_encode($courses);
