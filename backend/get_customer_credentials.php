<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

echo "=== Customer Portal Login Credentials ===\n\n";

$customer = Customer::first();

if (!$customer) {
    echo "❌ No customer found in database\n";
    exit(1);
}

// Set a simple password
$customer->password = Hash::make('password123');
$customer->password_is_temporary = false;
$customer->save();

echo "✅ Customer credentials ready!\n\n";
echo "Customer Portal Login:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Email:    {$customer->email}\n";
echo "Password: password123\n";
echo "Name:     {$customer->first_name} {$customer->last_name}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Steps to access Customer Portal:\n";
echo "1. Logout from admin panel\n";
echo "2. Go to: http://localhost:5173\n";
echo "3. Click 'Customer Portal' or 'Login'\n";
echo "4. Use the credentials above\n\n";

echo "Note: Admin login and Customer login are separate!\n";
echo "      Admin users cannot access the Customer Portal.\n";
