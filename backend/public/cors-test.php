<?php
// Simple CORS test file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo json_encode([
    'message' => 'CORS test successful',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders()
]);
?>