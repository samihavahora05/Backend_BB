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
    
    $controller = new \App\Http\Controllers\Api\Company\CompanyApplicantController();
    
    // Simulate updating an applicant status to offer_sent
    $req = new \Illuminate\Http\Request();
    $req->setMethod('PUT');
    $req->setUserResolver(function() use ($user) { return $user; });
    $req->merge(['status' => 'offer_sent']);
    
    $res = $controller->updateStatus($req, 4);
    
    echo "<pre>Update Status Response:\n"; print_r($res->getData(true)); echo "</pre>";
    
    $offController = new \App\Http\Controllers\Api\Company\CompanyOfferController();
    $offData = $offController->index($request)->getData(true);
    
    echo "<pre>Offers Response:\n"; print_r($offData); echo "</pre>";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
