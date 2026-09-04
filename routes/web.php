<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Fallback storage route for production environments where symlinks are not permitted/linked
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
    while (str_starts_with($cleanPath, 'storage/')) {
        $cleanPath = ltrim(substr($cleanPath, 8), '/');
    }

    if (Storage::disk('public')->exists($cleanPath)) {
        return Storage::disk('public')->response($cleanPath, null, [
            'Cache-Control' => 'public, max-age=31536000',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    abort(404, 'File not found in storage.');
})->where('path', '.*');

