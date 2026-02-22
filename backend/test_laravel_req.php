<?php
// Load facade aliases
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/quotes', 'POST', [
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
]);
$request->server->set('HTTP_ACCEPT', 'application/json');

$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
