<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;

echo "Final Status Check:\n";
echo "===================\n\n";

// Check current status distribution
$activeCount = Customer::where('status', 'active')->count();
$inactiveCount = Customer::where('status', 'inactive')->count();
$suspendedCount = Customer::where('status', 'suspended')->count();

echo "Current Status Distribution:\n";
echo "  Active: {$activeCount}\n";
echo "  Inactive: {$inactiveCount}\n";
echo "  Suspended: {$suspendedCount}\n";
echo "  Total: " . Customer::count() . "\n\n";

// Show a few examples
echo "Sample Customers:\n";
$samples = Customer::take(5)->get();
foreach ($samples as $customer) {
    echo "  ID {$customer->id}: {$customer->first_name} {$customer->last_name} - Status: {$customer->status} (is_active: " . ($customer->is_active ? 'true' : 'false') . ")\n";
}

echo "\n";

// Test updating a customer to suspended
$testCustomer = Customer::where('status', 'inactive')->first();
if ($testCustomer) {
    echo "Testing status update to 'suspended':\n";
    echo "  Before: ID {$testCustomer->id} - Status: {$testCustomer->status}\n";
    
    $testCustomer->status = 'suspended';
    $testCustomer->is_active = false;
    $testCustomer->save();
    
    $testCustomer->refresh();
    echo "  After: ID {$testCustomer->id} - Status: {$testCustomer->status}\n\n";
}

// Final count
$activeCount = Customer::where('status', 'active')->count();
$inactiveCount = Customer::where('status', 'inactive')->count();
$suspendedCount = Customer::where('status', 'suspended')->count();

echo "Final Status Distribution:\n";
echo "  Active: {$activeCount}\n";
echo "  Inactive: {$inactiveCount}\n";
echo "  Suspended: {$suspendedCount}\n";
echo "  Total: " . Customer::count() . "\n";
