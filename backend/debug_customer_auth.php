<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Customer Authentication Debug ===\n\n";

// Step 1: Check customer exists
$customer = Customer::where('email', 'john.doe@example.com')->first();

if (!$customer) {
    echo "❌ Customer not found!\n";
    exit(1);
}

echo "✅ Customer found in database:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->first_name} {$customer->last_name}\n";
echo "   Email: {$customer->email}\n";
echo "   Status: {$customer->status}\n";
echo "   Active: " . ($customer->is_active ? 'Yes' : 'No') . "\n\n";

// Step 2: Test login
echo "Testing customer login...\n";

$ch = curl_init('http://localhost:8000/api/auth/customer/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'john.doe@example.com',
    'password' => 'password123'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response (HTTP $httpCode):\n";
$loginData = json_decode($response, true);
echo json_encode($loginData, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode !== 200 || !isset($loginData['token'])) {
    echo "❌ Login failed!\n";
    if (isset($loginData['message'])) {
        echo "   Error: {$loginData['message']}\n";
    }
    exit(1);
}

$token = $loginData['token'];
echo "✅ Login successful! Token obtained.\n\n";

// Step 3: Test profile endpoint
echo "Testing profile endpoint...\n";

$ch = curl_init('http://localhost:8000/api/customer/profile');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Profile Response (HTTP $httpCode):\n";
$profileData = json_decode($response, true);
echo json_encode($profileData, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200 && isset($profileData['data'])) {
    echo "✅ Profile endpoint working!\n";
    echo "   Profile data structure is correct\n";
    echo "   Customer: {$profileData['data']['first_name']} {$profileData['data']['last_name']}\n\n";
} else {
    echo "❌ Profile endpoint failed!\n";
    if (isset($profileData['message'])) {
        echo "   Error: {$profileData['message']}\n";
    }
}

// Step 4: Summary
echo "=== Summary ===\n";
echo "Customer exists: ✅\n";
echo "Login works: " . ($httpCode === 200 && isset($loginData['token']) ? '✅' : '❌') . "\n";
echo "Profile endpoint: " . ($httpCode === 200 && isset($profileData['data']) ? '✅' : '❌') . "\n\n";

echo "Frontend should use:\n";
echo "  Email: john.doe@example.com\n";
echo "  Password: password123\n";
