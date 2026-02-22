<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

echo "=== Activating Customer Account ===\n\n";

$customer = Customer::where('email', 'john.doe@example.com')->first();

if (!$customer) {
    echo "❌ Customer not found\n";
    exit(1);
}

echo "Customer: {$customer->first_name} {$customer->last_name}\n";
echo "Current Status: {$customer->status}\n";
echo "Is Active: " . ($customer->is_active ? 'Yes' : 'No') . "\n\n";

// Activate the customer
$customer->status = 'active';
$customer->is_active = true;
$customer->password = Hash::make('password123');
$customer->password_is_temporary = false;
$customer->save();

echo "✅ Customer account activated!\n\n";
echo "Updated Status: {$customer->status}\n";
echo "Is Active: " . ($customer->is_active ? 'Yes' : 'No') . "\n\n";

echo "Login Credentials:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Email:    {$customer->email}\n";
echo "Password: password123\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "You can now login to the Customer Portal!\n";
