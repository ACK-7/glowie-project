<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing database connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "✓ Connected to database: " . DB::connection()->getDatabaseName() . "\n\n";
    
    echo "Checking tables:\n";
    $tables = DB::select('SHOW TABLES');
    
    if (count($tables) > 0) {
        echo "✓ Found " . count($tables) . " tables:\n";
        foreach($tables as $table) {
            $tableArray = (array)$table;
            echo "  - " . array_values($tableArray)[0] . "\n";
        }
    } else {
        echo "✗ No tables found! Migrations have not run.\n";
        echo "\nPlease run: php artisan migrate\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nCheck your .env file database credentials.\n";
}
