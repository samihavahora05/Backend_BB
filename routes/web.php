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
Route::get('/run-colleges-seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'ImportCollegesSeeder']);
    return response()->json(['message' => 'Colleges seeded successfully!', 'output' => \Illuminate\Support\Facades\Artisan::output()]);
});

Route::get('/download-logos', function () {
    ob_start();
    include base_path('download_logos.php');
    return response(ob_get_clean())->header('Content-Type', 'text/plain');
});

Route::get('/scrape-colleges', function () {
    $html = @file_get_contents('https://www.blueboxx.in/colleges');
    if (!$html) return response()->json(['error' => 'Could not fetch page']);
    
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    
    $images = $xpath->query('//img');
    $all_images = [];
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        if (strpos($src, 'http') !== 0 && strpos($src, 'data:image') !== 0) {
            $src = 'https://www.blueboxx.in/' . ltrim($src, '/');
        }
        $all_images[] = [
            'src' => $src,
            'alt' => $img->getAttribute('alt')
        ];
    }
    
    return response()->json([
        'total_images' => count($all_images),
        'images' => $all_images
    ]);
});

Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    return response()->json([
        'status' => 'success',
        'message' => 'Config, cache, route, and view caches have been cleared successfully!'
    ]);
});
