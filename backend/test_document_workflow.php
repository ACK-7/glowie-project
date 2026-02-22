<?php
/**
 * Test script to verify document upload workflow
 */

// Test the complete workflow: Quote -> Booking -> Document Upload -> Admin View

echo "=== Document Workflow Test ===\n";

$baseUrl = 'http://localhost:8000/api';
$adminToken = null;

// Step 1: Create a quote
echo "\n1. Creating a quote...\n";
$quoteData = [
    'vehicleType' => 'sedan',
    'year' => 2022,
    'make' => 'Toyota',
    'model' => 'Camry',
    'originCountry' => 'Japan',
    'destinationCountry' => 'Uganda',
    'shippingMethod' => 'roro',
    'fullName' => 'Test User',
    'email' => 'test_' . time() . '@example.com',
    'phone' => '1234567890'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/quotes");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($quoteData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$quoteResponse = curl_exec($ch);
$quoteInfo = curl_getinfo($ch);
curl_close($ch);

echo "Quote Status: " . $quoteInfo['http_code'] . "\n";
echo "Quote Response: " . $quoteResponse . "\n";

$quoteData = json_decode($quoteResponse, true);
if (!$quoteData || !isset($quoteData['reference'])) {
    echo "Failed to create quote\n";
    exit(1);
}

$quoteReference = $quoteData['reference'];
$email = $quoteData['customer']['email'] ?? $quoteData['email'] ?? 'test_' . time() . '@example.com';

echo "Quote created: $quoteReference for $email\n";

// Step 2: Admin login to approve quote
echo "\n2. Admin login...\n";
$adminLoginData = [
    'email' => 'admin@shipwithglowie.com',
    'password' => 'admin123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/admin/login");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($adminLoginData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$adminResponse = curl_exec($ch);
$adminInfo = curl_getinfo($ch);
curl_close($ch);

echo "Admin Login Status: " . $adminInfo['http_code'] . "\n";
echo "Admin Response: " . $adminResponse . "\n";

$adminData = json_decode($adminResponse, true);
if (!$adminData || !isset($adminData['token'])) {
    echo "Failed to login as admin\n";
    exit(1);
}

$adminToken = $adminData['token'];
echo "Admin logged in successfully\n";

// Step 3: Find and approve the quote
echo "\n3. Finding and approving quote...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/crud/quotes?search=$quoteReference");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);
$quotesResponse = curl_exec($ch);
$quotesInfo = curl_getinfo($ch);
curl_close($ch);

echo "Quotes Search Status: " . $quotesInfo['http_code'] . "\n";
echo "Quotes Response: " . $quotesResponse . "\n";

$quotesData = json_decode($quotesResponse, true);
if (!$quotesData || empty($quotesData['data'])) {
    echo "Quote not found in admin panel\n";
    exit(1);
}

$quote = null;
if (isset($quotesData['data']['data'])) {
    // Paginated response
    foreach ($quotesData['data']['data'] as $q) {
        if ($q['quote_reference'] === $quoteReference) {
            $quote = $q;
            break;
        }
    }
} else if (is_array($quotesData['data'])) {
    // Direct array response
    foreach ($quotesData['data'] as $q) {
        if ($q['quote_reference'] === $quoteReference) {
            $quote = $q;
            break;
        }
    }
}

if (!$quote) {
    echo "Quote not found in search results\n";
    exit(1);
}

$quoteId = $quote['id'];
echo "Found quote ID: $quoteId\n";

// Approve the quote
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/crud/quotes/$quoteId/approve");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['notes' => 'Approved for testing']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);
$approveResponse = curl_exec($ch);
$approveInfo = curl_getinfo($ch);
curl_close($ch);

echo "Quote Approval Status: " . $approveInfo['http_code'] . "\n";
echo "Approval Response: " . $approveResponse . "\n";

// Step 4: Create booking with documents
echo "\n4. Creating booking with documents...\n";

// Create temporary test files
$idDocContent = "This is a test ID document content";
$logbookContent = "This is a test logbook document content";

$idDocPath = tempnam(sys_get_temp_dir(), 'id_doc') . '.txt';
$logbookPath = tempnam(sys_get_temp_dir(), 'logbook') . '.txt';

file_put_contents($idDocPath, $idDocContent);
file_put_contents($logbookPath, $logbookContent);

// Create multipart form data
$postFields = [
    'quote_reference' => $quoteReference,
    'email' => $email,
    'id_document' => new CURLFile($idDocPath, 'text/plain', 'test_id.txt'),
    'logbook_document' => new CURLFile($logbookPath, 'text/plain', 'test_logbook.txt')
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/bookings/confirm");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$bookingResponse = curl_exec($ch);
$bookingInfo = curl_getinfo($ch);
curl_close($ch);

echo "Booking Status: " . $bookingInfo['http_code'] . "\n";
echo "Booking Response: " . $bookingResponse . "\n";

// Clean up temp files
unlink($idDocPath);
unlink($logbookPath);

$bookingData = json_decode($bookingResponse, true);
if (!$bookingData || !isset($bookingData['data']['id'])) {
    echo "Failed to create booking\n";
    exit(1);
}

$bookingId = $bookingData['data']['id'];
echo "Booking created: ID $bookingId\n";

// Step 5: Check documents in admin panel
echo "\n5. Checking documents in admin panel...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/crud/documents/booking/$bookingId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);
$documentsResponse = curl_exec($ch);
$documentsInfo = curl_getinfo($ch);
curl_close($ch);

echo "Documents Status: " . $documentsInfo['http_code'] . "\n";
echo "Documents Response: " . $documentsResponse . "\n";

$documentsData = json_decode($documentsResponse, true);
if ($documentsData && isset($documentsData['data']['documents'])) {
    $documents = $documentsData['data']['documents'];
    echo "Found " . count($documents) . " documents\n";
    
    foreach ($documents as $doc) {
        echo "- Document ID: " . $doc['id'] . ", Type: " . $doc['document_type'] . ", Status: " . $doc['status'] . "\n";
    }
    
    if (count($documents) > 0) {
        echo "\n✅ SUCCESS: Documents are appearing in admin dashboard!\n";
    } else {
        echo "\n❌ FAILURE: No documents found in admin dashboard\n";
    }
} else {
    echo "\n❌ FAILURE: Could not retrieve documents from admin dashboard\n";
}

echo "\n=== Test Complete ===\n";