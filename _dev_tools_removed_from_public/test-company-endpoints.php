<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $user = \App\Models\User::find(547);
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $controller = new \App\Http\Controllers\Api\Company\CompanyJobController();
    $data = $controller->index($request)->getData(true);
    
    echo "<pre>Jobs Response:\n"; print_r($data); echo "</pre>";
    
    $appController = new \App\Http\Controllers\Api\Company\CompanyApplicantController();
    $appData = $appController->index($request)->getData(true);
    
    echo "<pre>Applicants Response:\n"; print_r($appData); echo "</pre>";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
