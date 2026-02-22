<?php
$email = "booking_test_" . time() . "@example.com";

// 1. Create Quote
$ch_create = curl_init();
curl_setopt($ch_create, CURLOPT_URL, "http://localhost/api/quotes");
curl_setopt($ch_create, CURLOPT_POST, 1);
curl_setopt($ch_create, CURLOPT_POSTFIELDS, json_encode([
    "vehicleType" => "suv",
    "year" => 2023,
    "make" => "Toyota",
    "originCountry" => "Japan",
    "shippingMethod" => "container",
    "fullName" => "Booking User",
    "email" => $email,
    "phone" => "0000000",
]));
curl_setopt($ch_create, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_create, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$create_response = curl_exec($ch_create);
$create_data = json_decode($create_response, true);
curl_close($ch_create);

$ref = $create_data['reference'] ?? null;
echo "Created Quote: $ref\n";

if (!$ref) {
    die("Failed to create quote for testing.\n");
}

// 2. Confirm Booking (Create Booking)
$ch_booking = curl_init();
curl_setopt($ch_booking, CURLOPT_URL, "http://localhost/api/bookings/confirm");
curl_setopt($ch_booking, CURLOPT_POST, 1);
curl_setopt($ch_booking, CURLOPT_POSTFIELDS, json_encode([ // Sending JSON for now, file upload would need multipart/form-data
    "quote_reference" => $ref,
    "email" => $email,
    "recipient_name" => "Booking Recipient",
    // "id_document" => ... (Skipping file for simple curl JSON test)
]));
curl_setopt($ch_booking, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_booking, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

$booking_response = curl_exec($ch_booking);
$booking_info = curl_getinfo($ch_booking);
curl_close($ch_booking);

echo "Booking Status: " . $booking_info['http_code'] . "\n";
echo "Booking Response: " . $booking_response . "\n";
