<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Environment Variables Test ===\n";
echo "PUSHER_APP_ID: " . (env('PUSHER_APP_ID') ?: 'NOT SET') . "\n";
echo "PUSHER_APP_KEY: " . (env('PUSHER_APP_KEY') ?: 'NOT SET') . "\n";
echo "PUSHER_APP_SECRET: " . (env('PUSHER_APP_SECRET') ?: 'NOT SET') . "\n";
echo "PUSHER_APP_CLUSTER: " . (env('PUSHER_APP_CLUSTER') ?: 'NOT SET') . "\n";
echo "PUSHER_HOST: " . (env('PUSHER_HOST') ?: 'NOT SET') . "\n";
echo "PUSHER_PORT: " . (env('PUSHER_PORT') ?: 'NOT SET') . "\n";
echo "PUSHER_SCHEME: " . (env('PUSHER_SCHEME') ?: 'NOT SET') . "\n";

echo "\n=== Config Test ===\n";
echo "services.pusher.key: " . (config('services.pusher.key') ?: 'NOT SET') . "\n";
echo "services.pusher.secret: " . (config('services.pusher.secret') ?: 'NOT SET') . "\n";
echo "services.pusher.app_id: " . (config('services.pusher.app_id') ?: 'NOT SET') . "\n";
echo "services.pusher.cluster: " . (config('services.pusher.cluster') ?: 'NOT SET') . "\n";
echo "services.pusher.host: " . (config('services.pusher.host') ?: 'NOT SET') . "\n";

echo "\n=== Pusher Service Test ===\n";
try {
    $pusher = new \App\Services\PusherService();
    echo "PusherService created successfully\n";
    
    $pusherInstance = $pusher->getPusher();
    echo "Pusher instance created successfully\n";
    
    // Test a simple trigger
    $result = $pusher->sendMessage('test-channel', 'test-event', ['test' => 'data']);
    echo "Pusher trigger result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} 