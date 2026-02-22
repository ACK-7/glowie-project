<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Testing Profile Update ===\n\n";

// Get customer and create token
$customer = Customer::where('email', 'john.doe@example.com')->first();
$token = $customer->createToken('test')->plainTextToken;

echo "Customer: {$customer->first_name} {$customer->last_name}\n";
echo "Current Phone: {$customer->phone}\n\n";

// Test update
$updateUrl = 'http://localhost:8000/api/customer/profile';
$updateData = json_encode([
    'first_name' => 'John',
    'last_name' => 'Doe Updated',
    'phone' => '+256-700-999-999',
    'city' => 'Kampala Updated',
]);

echo "Testing profile update...\n";
echo "Update data: $updateData\n\n";

$ch = curl_init($updateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "✅ Profile updated successfully!\n\n";
        echo "Updated Information:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Name: " . $data['data']['first_name'] . " " . $data['data']['last_name'] . "\n";
        echo "Phone: " . $data['data']['phone'] . "\n";
        echo "City: " . $data['data']['city'] . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
} else {
    echo "❌ Update failed!\n";
}

// Verify in database
$customer->refresh();
echo "\nVerifying in database:\n";
echo "Last Name: {$customer->last_name}\n";
echo "Phone: {$customer->phone}\n";
echo "City: {$customer->city}\n";
