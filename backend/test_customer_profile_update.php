<?php

/**
 * Test Customer Profile Update
 * Verifies that customers can update their profile information
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Customer Profile Update Test ===\n\n";

// Get test customer
$customer = Customer::first();

if (!$customer) {
    echo "❌ No customer found\n";
    exit(1);
}

echo "Testing with customer: {$customer->full_name} ({$customer->email})\n\n";

// Generate token
$token = $customer->createToken('test-token')->plainTextToken;

// Test data
$updateData = [
    'first_name' => 'Updated',
    'last_name' => 'Name',
    'phone' => '+1234567890',
    'city' => 'Test City',
    'address' => '123 Updated Street',
];

echo "=== Testing Profile Update ===\n";
echo "Update data: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n\n";

// Make API request
$ch = curl_init('http://localhost:8000/api/customer/profile');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Profile updated successfully!\n\n";
    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    // Verify in database
    $customer->refresh();
    echo "=== Verified in Database ===\n";
    echo "First Name: {$customer->first_name}\n";
    echo "Last Name: {$customer->last_name}\n";
    echo "Phone: {$customer->phone}\n";
    echo "City: {$customer->city}\n";
    echo "Address: {$customer->address}\n\n";
    
    // Restore original data
    echo "Restoring original data...\n";
    $customer->update([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+256-700-123-001',
        'city' => 'Kampala',
        'address' => '123 Main Street, Kampala',
    ]);
    echo "✅ Original data restored\n";
    
    exit(0);
} else {
    echo "❌ Profile update failed\n";
    echo "Response: $response\n";
    exit(1);
}
