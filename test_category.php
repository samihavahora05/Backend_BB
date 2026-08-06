<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CourseCategory;
use Illuminate\Support\Str;

try {
    $category = CourseCategory::create([
        'name' => 'Test Category ' . time(),
        'slug' => 'test-category-' . time(),
        'description' => 'Test description',
        'position' => 1,
        'status' => 'active',
        'created_by' => 1
    ]);
    echo "Success: Created category ID " . $category->id;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
