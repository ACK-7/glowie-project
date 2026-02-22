<?php

/**
 * Customer Portal API Test Script
 * Tests all customer portal endpoints to ensure data is fetched correctly
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

echo "=== Customer Portal API Test ===\n\n";

// Get a test customer
$customer = Customer::with(['quotes', 'bookings'])->first();

if (!$customer) {
    echo "❌ No customer found in database\n";
    exit(1);
}

echo "✅ Test Customer Found:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->first_name} {$customer->last_name}\n";
echo "   Email: {$customer->email}\n\n";

// Generate a test token
$token = $customer->createToken('test-token')->plainTextToken;
echo "✅ Test Token Generated\n\n";

// Test API endpoints
$baseUrl = 'http://localhost:8000/api';
$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
];

function testEndpoint($name, $url, $headers) {
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = 0;
        
        if (isset($data['data'])) {
            $count = is_array($data['data']) ? count($data['data']) : 1;
        } elseif (isset($data['quotes'])) {
            $count = count($data['quotes']);
        } elseif (isset($data['bookings'])) {
            $count = count($data['bookings']);
        }
        
        echo "✅ SUCCESS (HTTP $httpCode) - Found $count items\n";
        return true;
    } else {
        echo "❌ FAILED (HTTP $httpCode)\n";
        if ($response) {
            $error = json_decode($response, true);
            echo "   Error: " . ($error['message'] ?? 'Unknown error') . "\n";
        }
        return false;
    }
}

echo "=== Testing Customer Portal Endpoints ===\n\n";

$tests = [
    'Profile' => "$baseUrl/customer/profile",
    'Quotes' => "$baseUrl/quotes",
    'Bookings' => "$baseUrl/bookings",
    'Documents' => "$baseUrl/documents",
    'Payments' => "$baseUrl/payments",
];

$results = [];
foreach ($tests as $name => $url) {
    $results[$name] = testEndpoint($name, $url, $headers);
    echo "\n";
}

// Summary
echo "=== Test Summary ===\n";
$passed = array_filter($results);
$total = count($results);
$passedCount = count($passed);

echo "Passed: $passedCount/$total\n";

foreach ($results as $name => $result) {
    echo ($result ? "✅" : "❌") . " $name\n";
}

if ($passedCount === $total) {
    echo "\n🎉 All tests passed! Customer portal is fully functional.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Please check the endpoints above.\n";
    exit(1);
}
