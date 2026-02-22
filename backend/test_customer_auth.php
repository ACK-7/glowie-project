<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Testing Customer Authentication & Profile ===\n\n";

// Test customer login
$customer = Customer::where('email', 'john.doe@example.com')->first();

if (!$customer) {
    echo "❌ Customer not found\n";
    exit(1);
}

echo "✅ Customer found: {$customer->first_name} {$customer->last_name}\n";
echo "   Email: {$customer->email}\n";
echo "   ID: {$customer->id}\n\n";

// Test login
$loginUrl = 'http://localhost:8000/api/auth/customer/login';
$loginData = json_encode([
    'email' => 'john.doe@example.com',
    'password' => 'password123'
]);

echo "Testing login...\n";
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: $loginHttpCode\n";

if ($loginHttpCode !== 200) {
    echo "❌ Login failed!\n";
    echo "Response: $loginResponse\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
echo "✅ Login successful!\n";

if (!isset($loginData['token'])) {
    echo "❌ No token in response!\n";
    echo "Response: " . json_encode($loginData, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$token = $loginData['token'];
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// Test profile endpoint
echo "Testing profile endpoint...\n";
$profileUrl = 'http://localhost:8000/api/customer/profile';

$ch = curl_init($profileUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$profileResponse = curl_exec($ch);
$profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Profile HTTP Code: $profileHttpCode\n";

if ($profileHttpCode !== 200) {
    echo "❌ Profile request failed!\n";
    echo "Response: $profileResponse\n";
    exit(1);
}

$profileData = json_decode($profileResponse, true);
echo "✅ Profile retrieved successfully!\n\n";

echo "Profile Response Structure:\n";
echo json_encode($profileData, JSON_PRETTY_PRINT) . "\n\n";

// Check if data is in correct format
if (isset($profileData['data'])) {
    echo "✅ Response has 'data' key\n";
    $customer = $profileData['data'];
    
    echo "\nCustomer Information:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID:         " . ($customer['id'] ?? 'missing') . "\n";
    echo "First Name: " . ($customer['first_name'] ?? 'missing') . "\n";
    echo "Last Name:  " . ($customer['last_name'] ?? 'missing') . "\n";
    echo "Email:      " . ($customer['email'] ?? 'missing') . "\n";
    echo "Phone:      " . ($customer['phone'] ?? 'missing') . "\n";
    echo "Country:    " . ($customer['country'] ?? 'missing') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🎉 Everything is working correctly!\n";
    echo "\nIf the frontend is not showing data, check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Network tab to see if API calls are being made\n";
    echo "3. Make sure you're logged in as customer (not admin)\n";
    echo "4. Try clearing browser cache and localStorage\n";
} else {
    echo "❌ Response does not have 'data' key\n";
    echo "This means the backend is not returning the correct format\n";
}
