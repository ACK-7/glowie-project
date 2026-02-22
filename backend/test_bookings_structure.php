<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

echo "=== Testing Bookings Data Structure ===\n\n";

$customer = Customer::where('email', 'john.doe@example.com')->first();
$token = $customer->createToken('test')->plainTextToken;

echo "Customer: {$customer->first_name} {$customer->last_name}\n\n";

// Test bookings endpoint
$bookingsUrl = 'http://localhost:8000/api/bookings';

$ch = curl_init($bookingsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    echo "Full Response Structure:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($data['data'])) {
        $bookingsData = $data['data'];
        
        if (is_array($bookingsData) && count($bookingsData) > 0) {
            // Check if it's an associative array or indexed array
            $keys = array_keys($bookingsData);
            if (is_numeric($keys[0])) {
                // Indexed array
                $firstBooking = $bookingsData[0];
            } else {
                // Associative array - might be a collection
                $firstBooking = reset($bookingsData);
            }
            
            echo "✅ First Booking Found\n\n";
            echo json_encode($firstBooking, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ No bookings in data array\n";
        }
    } else {
        echo "❌ No 'data' key in response\n";
    }
} else {
    echo "❌ Request failed\n";
    echo "Response: $response\n";
}
