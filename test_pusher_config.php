<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Pusher Configuration Check ===\n";

$pusherKey = env('PUSHER_APP_KEY');
$pusherSecret = env('PUSHER_APP_SECRET');
$pusherAppId = env('PUSHER_APP_ID');
$pusherCluster = env('PUSHER_APP_CLUSTER');
$broadcastConnection = env('BROADCAST_CONNECTION');

echo "BROADCAST_CONNECTION: " . ($broadcastConnection ?? 'null') . "\n";
echo "PUSHER_APP_KEY: " . ($pusherKey ?? 'null') . "\n";
echo "PUSHER_APP_SECRET: " . ($pusherSecret ? '[HIDDEN]' : 'null') . "\n";
echo "PUSHER_APP_ID: " . ($pusherAppId ?? 'null') . "\n";
echo "PUSHER_APP_CLUSTER: " . ($pusherCluster ?? 'null') . "\n";

// Test configuration loading
$broadcastConfig = config('broadcasting.connections.pusher');
echo "\n=== Broadcasting Config ===\n";
echo "Default driver: " . config('broadcasting.default') . "\n";
echo "Pusher key from config: " . ($broadcastConfig['key'] ?? 'null') . "\n";
echo "Pusher cluster from config: " . ($broadcastConfig['options']['cluster'] ?? 'null') . "\n";

echo "\nDone.\n";
