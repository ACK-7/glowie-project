<?php
// Quick diagnostic script to check database constraint status

require __DIR__ . '/vendor/autoload.php';

$env = parse_ini_file(__DIR__ . '/.env');

try {
    $pdo = new PDO(
        "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_DATABASE'],
        $env['DB_USERNAME'],
        $env['DB_PASSWORD']
    );
    
    echo "✅ Connected to database: " . $env['DB_DATABASE'] . "\n\n";
    
    // Check bookings table constraints
    echo "=== Checking bookings table foreign keys ===\n";
    $result = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_id'
    ");
    
    $constraints = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "❌ NO CONSTRAINT FOUND for customer_id!\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "Constraint: " . $constraint['CONSTRAINT_NAME'] . "\n";
            echo "  Column: " . $constraint['COLUMN_NAME'] . "\n";
            echo "  References: " . $constraint['REFERENCED_TABLE_NAME'] . "(" . $constraint['REFERENCED_COLUMN_NAME'] . ")\n";
            
            if ($constraint['REFERENCED_TABLE_NAME'] === 'users') {
                echo "  ❌ ERROR: Should reference 'customers' table, not 'users'!\n";
            } else if ($constraint['REFERENCED_TABLE_NAME'] === 'customers') {
                echo "  ✅ CORRECT: References customers table\n";
            }
        }
    }
    
    echo "\n=== Bookings table structure ===\n";
    $tableResult = $pdo->query("SHOW CREATE TABLE bookings");
    $tableInfo = $tableResult->fetch(PDO::FETCH_ASSOC);
    echo $tableInfo['Create Table'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
