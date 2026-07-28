<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/run-test', function () {
    ob_start();
    require base_path('test_course_workflow.php');
    return response(ob_get_clean())->header('Content-Type', 'application/json');
});

Route::get('/run-seed', function () {
    ob_start();
    require base_path('seed_dashboard_v2.php');
    return response(ob_get_clean())->header('Content-Type', 'text/plain');
});
