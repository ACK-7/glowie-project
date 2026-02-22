<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/quotes");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "vehicleType" => "sedan",
    "year" => 2020,
    "make" => "Toyota",
    "model" => "Camry",
    "originCountry" => "Japan",
    "shippingMethod" => "roro",
    "fullName" => "Test User " . time(),
    "email" => "test" . time() . "@example.com",
    "phone" => "1234567890",
    "deliveryLocation" => "Kampala"
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$output = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status: " . $info['http_code'] . "\n";
echo "Response: " . $output . "\n";
