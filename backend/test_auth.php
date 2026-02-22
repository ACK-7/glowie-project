<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force DB Connection to localhost for host-machine execution
config([
    'database.connections.mysql.host' => '127.0.0.1',
    'database.connections.mysql.port' => '3306',
]);

echo "--- AUTH DIAGNOSTICS ---\n";
$email = 'admin@shipwithglowie.com';
$password = 'admin123';

$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "❌ User '$email' NOT FOUND in database.\n";
    exit(1);
}

echo "✅ User found (ID: {$user->id})\n";
echo "   Role: {$user->role}\n";
echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";

$check = \Illuminate\Support\Facades\Hash::check($password, $user->password);

if ($check) {
    echo "✅ Password '$password' MATCHES the stored hash.\n";
    echo "Login should work if middleware/token creation is fine.\n";
} else {
    echo "❌ Password '$password' DOES NOT MATCH stored hash.\n";
    echo "   Stored Hash starts with: " . substr($user->password, 0, 10) . "...\n";
    
    echo "🔄 FIXING PASSWORD NOW...\n";
    $user->password = \Illuminate\Support\Facades\Hash::make($password);
    $user->save();
    echo "✅ Password successfully reset to '$password'. Try logging in now.\n";
}
