<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Environment variable test:\n";
echo "APP_NAME: " . env('APP_NAME', 'not set') . "\n";
echo "DB_HOST: " . env('DB_HOST', 'not set') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST', 'not set') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT', 'not set') . "\n";

echo "\nDirect file read test:\n";
$envContent = file_get_contents('.env');
$lines = explode("\n", $envContent);
foreach ($lines as $line) {
    if (strpos($line, 'MAIL_HOST') === 0 || strpos($line, 'MAIL_PORT') === 0) {
        echo $line . "\n";
    }
}