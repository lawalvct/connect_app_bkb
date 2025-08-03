<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Environment Variables:\n";
echo "PUSHER_APP_KEY: " . (env('PUSHER_APP_KEY') ?: 'NULL') . "\n";
echo "PUSHER_APP_SECRET: " . (env('PUSHER_APP_SECRET') ?: 'NULL') . "\n";
echo "PUSHER_APP_ID: " . (env('PUSHER_APP_ID') ?: 'NULL') . "\n";
echo "PUSHER_APP_CLUSTER: " . (env('PUSHER_APP_CLUSTER') ?: 'NULL') . "\n";
echo "BROADCAST_CONNECTION: " . (env('BROADCAST_CONNECTION') ?: 'NULL') . "\n";

echo "\nConfig Values:\n";
echo "broadcasting.default: " . config('broadcasting.default') . "\n";
echo "broadcasting.connections.pusher.key: " . (config('broadcasting.connections.pusher.key') ?: 'NULL') . "\n";
echo "broadcasting.connections.pusher.secret: " . (config('broadcasting.connections.pusher.secret') ?: 'NULL') . "\n";
echo "broadcasting.connections.pusher.app_id: " . (config('broadcasting.connections.pusher.app_id') ?: 'NULL') . "\n";
echo "broadcasting.connections.pusher.options.cluster: " . (config('broadcasting.connections.pusher.options.cluster') ?: 'NULL') . "\n";
