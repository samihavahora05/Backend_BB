<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $user = \App\Models\User::find(547);
    if (!$user) {
        echo "User 547 not found";
        exit;
    }
    
    // Simulate request with this user
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $controller = new \App\Http\Controllers\Api\Company\CompanyDashboardController();
    $data = $controller->index($request)->getData(true);
    
    echo "<pre>"; print_r($data); echo "</pre>";
} catch (\Exception $e) {
    echo $e->getMessage();
}
