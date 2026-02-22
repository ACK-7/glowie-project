<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Notification;
use App\Models\Customer;

echo "Testing notification database structure...\n";

try {
    // Test creating a notification record
    $notification = Notification::create([
        'notifiable_type' => Customer::class,
        'notifiable_id' => 1,
        'type' => 'test_notification',
        'title' => 'Test Notification',
        'message' => 'This is a test notification',
        'data' => ['test' => 'data'],
        'channels' => ['database', 'email'],
        'is_read' => false,
    ]);
    
    echo "Notification created successfully with ID: {$notification->id}\n";
    
    // Clean up
    $notification->delete();
    echo "Test notification cleaned up.\n";
    
    echo "Database structure is working correctly!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}