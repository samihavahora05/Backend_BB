<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('role', 'student')->first();
$token = $user->createToken('test')->plainTextToken;

$ch = curl_init('http://127.0.0.1:8000/api/public/internships');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "Debug User ID: " . ($data['debug_user_id'] ?? 'NULL') . "\n";
if (isset($data['data'])) {
    foreach ($data['data'] as $item) {
        echo "Internship ID: {$item['id']} | Title: {$item['title']} | Has Applied: " . ($item['has_applied'] ? 'true' : 'false') . "\n";
    }
} else {
    echo "No data. Content: " . $response;
}
