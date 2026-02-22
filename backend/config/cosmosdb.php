<?php

return [
    'enabled' => env('COSMOS_DB_ENABLED', false),
    'connection_string' => env('COSMOS_DB_CONNECTION_STRING'),
    'database' => env('COSMOS_DB_DATABASE', 'shipwithglowie-chat'),
    'container' => env('COSMOS_DB_CONTAINER', 'conversations'),
    'partition_key' => env('COSMOS_DB_PARTITION_KEY', '/userId'),
    'max_retries' => 3,
    'retry_delay_ms' => 100,
];
