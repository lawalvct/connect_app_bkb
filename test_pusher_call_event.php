<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Pusher Broadcasting Test for CallInitiated Event ===\n\n";

try {
    // Test 1: Check broadcasting configuration
    echo "1. Checking broadcasting configuration...\n";
    $broadcastConnection = config('broadcasting.default');
    echo "   BROADCAST_CONNECTION: {$broadcastConnection}\n";

    if ($broadcastConnection !== 'pusher') {
        echo "   ❌ WARNING: Broadcasting is not set to 'pusher'\n";
        echo "   Current setting: {$broadcastConnection}\n";
        echo "   Please set BROADCAST_CONNECTION=pusher in your .env file\n\n";
    } else {
        echo "   ✅ Broadcasting is correctly set to 'pusher'\n\n";
    }

    // Test 2: Check Pusher configuration
    echo "2. Checking Pusher configuration...\n";
    $pusherConfig = config('broadcasting.connections.pusher');
    echo "   App ID: " . ($pusherConfig['app_id'] ?? 'NOT SET') . "\n";
    echo "   Key: " . ($pusherConfig['key'] ?? 'NOT SET') . "\n";
    echo "   Secret: " . (isset($pusherConfig['secret']) ? str_repeat('*', strlen($pusherConfig['secret'])) : 'NOT SET') . "\n";
    echo "   Cluster: " . ($pusherConfig['options']['cluster'] ?? 'NOT SET') . "\n\n";

    // Test 3: Test direct Pusher connection
    echo "3. Testing direct Pusher connection...\n";

    $pusher = new \Pusher\Pusher(
        $pusherConfig['key'],
        $pusherConfig['secret'],
        $pusherConfig['app_id'],
        [
            'cluster' => $pusherConfig['options']['cluster'],
            'useTLS' => true
        ]
    );

    $testData = [
        'test' => 'Direct Pusher test',
        'timestamp' => now()->toISOString(),
        'message' => 'This is a test event from Laravel'
    ];

    $result = $pusher->trigger('private-conversation.1', 'test.event', $testData);
    echo "   Direct Pusher trigger result: " . json_encode($result) . "\n\n";

    // Test 4: Test CallInitiated event broadcasting
    echo "4. Testing CallInitiated event...\n";

    // Find a test call and conversation
    $call = \App\Models\Call::first();
    $conversation = \App\Models\Conversation::first();
    $user = \App\Models\User::first();

    if ($call && $conversation && $user) {
        echo "   Found test data:\n";
        echo "   - Call ID: {$call->id}\n";
        echo "   - Conversation ID: {$conversation->id}\n";
        echo "   - User ID: {$user->id}\n\n";

        // Create and broadcast the event
        echo "   Broadcasting CallInitiated event...\n";
        $event = new \App\Events\CallInitiated($call, $conversation, $user);

        // Test the event data
        $broadcastData = $event->broadcastWith();
        echo "   Event data that will be sent:\n";
        echo "   " . json_encode($broadcastData, JSON_PRETTY_PRINT) . "\n\n";

        // Test the channel
        $channel = $event->broadcastOn();
        echo "   Broadcasting on channel: {$channel->name}\n";
        echo "   Event name: {$event->broadcastAs()}\n\n";

        // Actually broadcast the event
        try {
            broadcast($event)->toOthers();
            echo "   ✅ CallInitiated event broadcasted successfully!\n";
            echo "   Check your Pusher debug console for channel: {$channel->name}\n";
            echo "   Look for event: {$event->broadcastAs()}\n\n";
        } catch (\Exception $e) {
            echo "   ❌ Failed to broadcast CallInitiated event: " . $e->getMessage() . "\n\n";
        }

    } else {
        echo "   ❌ No test data found (call, conversation, or user missing)\n";
        echo "   Please create test data first\n\n";
    }

    // Test 5: Instructions for debugging
    echo "5. Debugging steps:\n";
    echo "   a) Open Pusher debug console: https://dashboard.pusher.com/apps/{$pusherConfig['app_id']}/console\n";
    echo "   b) Subscribe to channel: private-conversation.{$conversation->id}\n";
    echo "   c) Look for event: call.initiated\n";
    echo "   d) Trigger a call using: POST /api/v1/calls/initiate\n\n";

    echo "✅ Test completed! Check your Pusher debug console.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
