<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test the NotificationService directly
use App\Services\NotificationService;
use App\Models\Customer;
use App\Models\Quote;

echo "Testing NotificationService email functionality...\n";

try {
    // Create a test customer
    $customer = new Customer();
    $customer->id = 999;
    $customer->first_name = 'Test';
    $customer->last_name = 'Customer';
    $customer->email = 'test@example.com';
    
    // Create a test quote
    $quote = new Quote();
    $quote->id = 999;
    $quote->customer_id = 999;
    $quote->quote_reference = 'QT2026010999';
    $quote->total_amount = 1500.00;
    $quote->currency = 'USD';
    $quote->valid_until = now()->addDays(30);
    
    // Test the notification service
    $notificationService = new NotificationService();
    $notificationService->sendQuoteApprovedNotification($quote, 'TempPass123');
    
    echo "Quote approved notification sent successfully!\n";
    echo "Check Mailhog at http://localhost:8025 for the email.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}