<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app['config']->set('app.debug', true);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/quotes', 'POST', [
    'vehicleType'=>'sedan', 
    'year'=>2020, 
    'make'=>'Toyota', 
    'fullName'=>'Test User', 
    'email'=>'test@test.com', 
    'phone'=>'1234567890', 
    'originCountry'=>'Japan', 
    'shippingMethod'=>'roro'
]);
$request->headers->set('Accept', 'application/json');

$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content Preview:\n";
$content = $response->getContent();
echo substr($content, 0, 600) . "\n\n";

$json = json_decode($content, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Parsed Error Details:\n";
    echo "Message: " . ($json['message'] ?? 'N/A') . "\n";
    echo "Exception: " . ($json['exception'] ?? 'N/A') . "\n";
    echo "File: " . ($json['file'] ?? 'N/A') . "\n";
    echo "Line: " . ($json['line'] ?? 'N/A') . "\n";
    if (isset($json['trace'])) {
        echo "Trace Top:\n";
        print_r(array_slice($json['trace'], 0, 3));
    }
} else {
    echo "Could not parse JSON.\n";
}
