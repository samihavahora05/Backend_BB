<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (function_exists('opcache_invalidate')) {
    opcache_invalidate('D:\\blueboxx\\blueboxx web\\backend\\app\\Http\\Controllers\\Api\\Admin\\AdminCertificateController.php', true);
}

$scanData = [
    'frontend_pages' => [],
    'backend_routes' => [],
    'api_endpoints' => []
];

try {

// 1. Scan Next.js Pages
$pagesDir = 'D:/blueboxx/blueboxx web/pages';
if (is_dir($pagesDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pagesDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['tsx', 'js', 'jsx'])) {
            $path = str_replace('\\', '/', $file->getPathname());
            $relPath = str_replace($pagesDir, '', $path);
            
            // Skip core nextjs files
            if (str_contains($relPath, '/api/') || str_contains($relPath, '_app') || str_contains($relPath, '_document')) {
                continue;
            }
            
            // Convert to route
            $route = str_replace(['.tsx', '.jsx', '.js', '/index'], ['', '', '', ''], $relPath);
            if ($route === '') $route = '/';
            $scanData['frontend_pages'][] = $route;
        }
    }
}

// 2. Scan Backend Routes
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = app('router')->getRoutes();
foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', $route->methods());
    
    // Ignore internal laravel routes
    if (str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum')) {
        continue;
    }
    
    $routeInfo = [
        'uri' => $uri,
        'methods' => $methods,
        'action' => $route->getActionName(),
        'middleware' => implode(', ', $route->gatherMiddleware() ?? [])
    ];
    
    if (str_starts_with($uri, 'api/')) {
        $scanData['api_endpoints'][] = $routeInfo;
    } else {
        $scanData['backend_routes'][] = $routeInfo;
    }
}

header('Content-Type: application/json');
echo json_encode($scanData, JSON_PRETTY_PRINT);

} catch (\Exception $e) {
    http_response_code(200);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (\Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
