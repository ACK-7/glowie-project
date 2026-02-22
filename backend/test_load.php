<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php'; // Boot app if needed for aliases

echo "Testing Load...\n";
if (class_exists(\App\Http\Controllers\QuoteController::class)) {
    echo "Class Loaded Successfully via class_exists.\n";
} else {
    echo "Class NOT Loaded via class_exists.\n";
}

try {
    $ref = new ReflectionClass(\App\Http\Controllers\QuoteController::class);
    echo "Reflection Success: " . $ref->getFileName() . "\n";
} catch (\Throwable $e) {
    echo "Reflection Error: " . $e->getMessage() . "\n";
}
