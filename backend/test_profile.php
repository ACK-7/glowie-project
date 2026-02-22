<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Testing Customer Profile Endpoint ===\n\n";

$customer = Customer::first();
$token = $customer->createToken('test')->plainTextToken;

echo "Customer: {$customer->first_name} {$customer->last_name}\n";
echo "Email: {$customer->email}\n\n";

$ch = curl_init('http://localhost:8000/api/customer/profile');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

$data = json_decode($response, true);

if (isset($data['data'])) {
    echo "\n✅ Profile data structure is correct!\n";
    echo "Profile contains:\n";
    echo "  - ID: " . ($data['data']['id'] ?? 'missing') . "\n";
    echo "  - Name: " . ($data['data']['first_name'] ?? 'missing') . " " . ($data['data']['last_name'] ?? 'missing') . "\n";
    echo "  - Email: " . ($data['data']['email'] ?? 'missing') . "\n";
    echo "  - Phone: " . ($data['data']['phone'] ?? 'missing') . "\n";
} else {
    echo "\n❌ Profile data structure is incorrect!\n";
    echo "Expected 'data' key in response\n";
}
