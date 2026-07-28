<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(547);
$request = Illuminate\Http\Request::create('/api/company/applicants', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = new \App\Http\Controllers\Api\Company\CompanyApplicantController();
$response = $controller->index($request);

echo "Content: " . $response->getContent() . "\n";
