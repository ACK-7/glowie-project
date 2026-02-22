<?php

/**
 * Complete Customer Portal Data Fetch Test
 * Verifies all customer portal endpoints and data relationships
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\Document;
use App\Models\Payment;

echo "=== Complete Customer Portal Data Fetch Test ===\n\n";

// Get a test customer with all relationships
$customer = Customer::with([
    'quotes' => function($query) {
        $query->latest()->limit(5);
    },
    'bookings' => function($query) {
        $query->latest()->limit(5);
    },
    'documents' => function($query) {
        $query->latest()->limit(5);
    },
    'payments' => function($query) {
        $query->latest()->limit(5);
    }
])->first();

if (!$customer) {
    echo "❌ No customer found in database\n";
    echo "Creating test customer...\n";
    
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test.customer@example.com',
        'phone' => '+1234567890',
        'password' => bcrypt('password'),
        'country' => 'United States',
        'city' => 'New York',
        'address' => '123 Test Street',
        'is_active' => true,
        'is_verified' => true,
    ]);
    
    echo "✅ Test customer created\n\n";
}

echo "=== Customer Profile Data ===\n";
echo "ID: {$customer->id}\n";
echo "Name: {$customer->full_name}\n";
echo "Email: {$customer->email}\n";
echo "Phone: {$customer->phone}\n";
echo "Country: {$customer->country}\n";
echo "City: {$customer->city}\n";
echo "Status: {$customer->status}\n";
echo "Active: " . ($customer->is_active ? 'Yes' : 'No') . "\n";
echo "Verified: " . ($customer->is_verified ? 'Yes' : 'No') . "\n";
echo "Last Login: " . ($customer->last_login_at ?? 'Never') . "\n\n";

echo "=== Customer Statistics ===\n";
echo "Total Bookings: {$customer->total_bookings}\n";
echo "Total Spent: $" . number_format($customer->total_spent, 2) . "\n";
echo "Customer Tier: {$customer->getCustomerTier()}\n";
echo "Discount Percentage: {$customer->getDiscountPercentage()}%\n";
echo "Average Booking Value: $" . number_format($customer->getAverageBookingValue(), 2) . "\n";
echo "Has Active Bookings: " . ($customer->hasActiveBookings() ? 'Yes' : 'No') . "\n";
echo "Pending Payments: $" . number_format($customer->getPendingPayments(), 2) . "\n\n";

echo "=== Related Data ===\n";
echo "Quotes: " . $customer->quotes->count() . " records\n";
if ($customer->quotes->count() > 0) {
    foreach ($customer->quotes as $quote) {
        echo "  - Quote #{$quote->quote_reference}: {$quote->status} - $" . number_format($quote->total_amount, 2) . "\n";
    }
}
echo "\n";

echo "Bookings: " . $customer->bookings->count() . " records\n";
if ($customer->bookings->count() > 0) {
    foreach ($customer->bookings as $booking) {
        echo "  - Booking #{$booking->booking_reference}: {$booking->status} - $" . number_format($booking->total_amount, 2) . "\n";
    }
}
echo "\n";

echo "Documents: " . $customer->documents->count() . " records\n";
if ($customer->documents->count() > 0) {
    foreach ($customer->documents as $document) {
        echo "  - {$document->document_type}: {$document->status}\n";
    }
}
echo "\n";

echo "Payments: " . $customer->payments->count() . " records\n";
if ($customer->payments->count() > 0) {
    foreach ($customer->payments as $payment) {
        echo "  - Payment #{$payment->payment_reference}: {$payment->status} - $" . number_format($payment->amount, 2) . "\n";
    }
}
echo "\n";

// Test API endpoints
echo "=== Testing API Endpoints ===\n\n";

$token = $customer->createToken('test-token')->plainTextToken;
$baseUrl = 'http://localhost:8000/api';
$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
];

function testApiEndpoint($name, $url, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Testing: $name\n";
    
    if ($error) {
        echo "❌ CURL Error: $error\n\n";
        return false;
    }
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        // Count items
        $count = 0;
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                $count = count($data['data']);
            } else {
                $count = 1;
            }
        } elseif (isset($data['quotes'])) {
            $count = count($data['quotes']);
        } elseif (isset($data['bookings'])) {
            $count = count($data['bookings']);
        } elseif (isset($data['documents'])) {
            $count = count($data['documents']);
        } elseif (isset($data['payments'])) {
            $count = count($data['payments']);
        }
        
        echo "✅ SUCCESS (HTTP $httpCode)\n";
        echo "   Items: $count\n";
        echo "   Response keys: " . implode(', ', array_keys($data)) . "\n\n";
        return true;
    } else {
        echo "❌ FAILED (HTTP $httpCode)\n";
        if ($response) {
            $error = json_decode($response, true);
            echo "   Error: " . ($error['message'] ?? 'Unknown error') . "\n";
            if (isset($error['errors'])) {
                echo "   Details: " . json_encode($error['errors']) . "\n";
            }
        }
        echo "\n";
        return false;
    }
}

$endpoints = [
    'Customer Profile' => "$baseUrl/customer/profile",
    'Quotes List' => "$baseUrl/quotes",
    'Bookings List' => "$baseUrl/bookings",
    'Documents List' => "$baseUrl/documents",
    'Payments List' => "$baseUrl/payments",
];

$results = [];
foreach ($endpoints as $name => $url) {
    $results[$name] = testApiEndpoint($name, $url, $headers);
}

// Test specific item endpoints if data exists
if ($customer->quotes->count() > 0) {
    $quoteId = $customer->quotes->first()->id;
    $results['Quote Details'] = testApiEndpoint(
        'Quote Details',
        "$baseUrl/quotes/$quoteId",
        $headers
    );
}

if ($customer->bookings->count() > 0) {
    $bookingId = $customer->bookings->first()->id;
    $results['Booking Details'] = testApiEndpoint(
        'Booking Details',
        "$baseUrl/bookings/$bookingId",
        $headers
    );
}

// Summary
echo "=== Test Summary ===\n";
$passed = array_filter($results);
$total = count($results);
$passedCount = count($passed);

echo "Passed: $passedCount/$total\n\n";

foreach ($results as $name => $result) {
    echo ($result ? "✅" : "❌") . " $name\n";
}

if ($passedCount === $total) {
    echo "\n🎉 All tests passed! Customer portal is fetching data correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Please check the endpoints above.\n";
    exit(1);
}
