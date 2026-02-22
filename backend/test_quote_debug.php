<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Quote;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;

echo "=== Quote Debug Test ===\n";

try {
    // Check database connection
    echo "1. Testing database connection...\n";
    $quotesCount = Quote::count();
    echo "   Total quotes in database: $quotesCount\n";
    
    // Check recent quotes
    echo "\n2. Recent quotes:\n";
    $recentQuotes = Quote::with(['customer', 'route'])
        ->latest()
        ->take(5)
        ->get(['id', 'quote_reference', 'status', 'customer_id', 'route_id', 'total_amount', 'created_at']);
    
    foreach ($recentQuotes as $quote) {
        echo "   ID: {$quote->id}, Ref: {$quote->quote_reference}, Status: {$quote->status}, ";
        echo "Customer: " . ($quote->customer ? $quote->customer->email : 'N/A') . ", ";
        echo "Amount: {$quote->total_amount}, Created: {$quote->created_at}\n";
    }
    
    // Check admin users
    echo "\n3. Admin users:\n";
    $adminUsers = User::where('role', 'admin')->orWhere('role', 'super_admin')->get(['id', 'name', 'email', 'role']);
    foreach ($adminUsers as $user) {
        echo "   ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$user->role}\n";
    }
    
    // Check customers
    echo "\n4. Recent customers:\n";
    $recentCustomers = Customer::latest()->take(3)->get(['id', 'first_name', 'last_name', 'email', 'created_at']);
    foreach ($recentCustomers as $customer) {
        echo "   ID: {$customer->id}, Name: {$customer->first_name} {$customer->last_name}, Email: {$customer->email}, Created: {$customer->created_at}\n";
    }
    
    // Check routes
    echo "\n5. Available routes:\n";
    $routes = Route::take(3)->get(['id', 'origin_country', 'destination_country', 'base_price']);
    foreach ($routes as $route) {
        echo "   ID: {$route->id}, Route: {$route->origin_country} -> {$route->destination_country}, Price: {$route->base_price}\n";
    }
    
    echo "\n=== Test completed successfully ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}