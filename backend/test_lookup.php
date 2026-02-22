<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1/api/quotes/lookup");
curl_setopt($ch, CURLOPT_POST, 1);

// 1. Create Quote
$email = "lookup_test_" . time() . "@example.com";
$ch_create = curl_init();
curl_setopt($ch_create, CURLOPT_URL, "http://127.0.0.1/api/quotes");
curl_setopt($ch_create, CURLOPT_POST, 1);
curl_setopt($ch_create, CURLOPT_POSTFIELDS, json_encode([
    "vehicleType" => "sedan",
    "year" => 2022,
    "make" => "Honda",
    "originCountry" => "Japan",
    "shippingMethod" => "roro",
    "fullName" => "Lookup User",
    "email" => $email,
    "phone" => "0000000",
]));
curl_setopt($ch_create, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_create, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$create_response = curl_exec($ch_create);
$create_info = curl_getinfo($ch_create);
echo "Create Status: " . $create_info['http_code'] . "\n";
echo "Create Response: " . $create_response . "\n";

$create_data = json_decode($create_response, true);
curl_close($ch_create);

$ref = $create_data['reference'] ?? 'QT-00000';
echo "Created Quote: $ref for $email\n";

// 2. Lookup Quote
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "reference" => $ref,
    "email" => $email
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$output = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Lookup Status: " . $info['http_code'] . "\n";
echo "Lookup Response: " . $output . "\n";
