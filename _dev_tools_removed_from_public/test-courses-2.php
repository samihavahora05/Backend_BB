<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$courses = \App\Models\Course::all();
echo json_encode($courses);
