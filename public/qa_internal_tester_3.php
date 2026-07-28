<?php
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
error_reporting(E_ALL);
ignore_user_abort(true);

if (function_exists('opcache_reset')) {
    opcache_reset();
}

try {
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Use Console Kernel to bootstrap it.
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

// Define a fake login route to prevent "Route [login] not defined" 500 errors
app('router')->get('login', function() { return 'login'; })->name('login');

$apiRoutes = [];
$routes = app('router')->getRoutes();
foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', $route->methods());
    
    if (str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum') || str_starts_with($uri, 'broadcasting')) continue;
    
    if (str_starts_with($uri, 'api/')) {
        $apiRoutes[] = [
            'uri' => $uri,
            'methods' => $methods,
        ];
    }
}

$results = [
    'passed' => [],
    'failed_500' => [],
    'failed_other' => []
];

// Test with pagination
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;

$filteredRoutes = [];
foreach ($apiRoutes as $route) {
    if (!str_contains($route['methods'], 'GET')) continue;
    if (str_contains($route['uri'], '{')) continue;
    if (str_starts_with($route['uri'], 'api/dev/')) continue; // Skip dev routes
    $filteredRoutes[] = $route;
}

$routesToTest = array_slice($filteredRoutes, $start, $limit);
$results['testing'] = $routesToTest;

foreach ($routesToTest as $route) {
    $uri = '/' . $route['uri'];
    
    // Create an internal request
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $request->headers->set('Accept', 'application/json');
    
    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        
        $resObj = ['path' => $uri, 'status' => $status];
        if ($status >= 200 && $status < 400) {
            $results['passed'][] = $resObj;
        } else if ($status === 500) {
            $content = $response->getContent();
            $resObj['error'] = substr(strip_tags($content), 0, 500); // Save part of error
            $results['failed_500'][] = $resObj;
        } else {
            $resObj['error'] = substr(strip_tags($response->getContent()), 0, 100);
            $results['failed_other'][] = $resObj;
        }
        
        $kernel->terminate($request, $response);
    } catch (\Exception $e) {
        $results['failed_500'][] = ['path' => $uri, 'status' => 500, 'error' => $e->getMessage()];
    } catch (\Throwable $e) {
        $results['failed_500'][] = ['path' => $uri, 'status' => 500, 'error' => $e->getMessage()];
    }
    // Save incremental progress
    file_put_contents(__DIR__.'/qa_internal_report_3.json', json_encode($results, JSON_PRETTY_PRINT));
}

} catch (\Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
