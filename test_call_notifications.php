<?php

// Test Pusher Call Notifications
// Run this file by accessing: http://your-domain.com/test_call_notifications.php

require_once __DIR__ . '/vendor/autoload.php';

use App\Events\CallInitiated;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Pusher Call Notifications...\n\n";

try {
    // Test 1: Direct Pusher connection
    echo "1. Testing direct Pusher connection...\n";

    $pusher = new \Pusher\Pusher(
        config('broadcasting.connections.pusher.key'),
        config('broadcasting.connections.pusher.secret'),
        config('broadcasting.connections.pusher.app_id'),
        [
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'useTLS' => true
        ]
    );

    $testData = [
        'test_message' => 'Direct Pusher test for call notifications',
        'timestamp' => now()->toISOString(),
        'call_id' => 'test-call-123',
        'call_type' => 'voice',
        'initiator' => [
            'id' => 1,
            'name' => 'Test User',
            'username' => 'testuser',
            'profile_url' => null,
        ],
    ];

    $result = $pusher->trigger('private-conversation.1', 'call.initiated', $testData);
    echo "Direct Pusher result: " . json_encode($result) . "\n\n";

    // Test 2: Find test users and conversation
    echo "2. Testing with real models...\n";

    $initiator = User::first();
    $conversation = Conversation::first();

    if (!$initiator || !$conversation) {
        echo "Error: No users or conversations found. Please create test data first.\n";
        exit;
    }

    echo "Found initiator: {$initiator->name} (ID: {$initiator->id})\n";
    echo "Found conversation: ID {$conversation->id}\n";

    // Test 3: Create a test call
    echo "\n3. Creating test call...\n";

    $call = Call::create([
        'conversation_id' => $conversation->id,
        'initiator_id' => $initiator->id,
        'call_type' => 'voice',
        'status' => 'initiated',
        'agora_channel_name' => 'test_channel_' . time(),
        'started_at' => now(),
    ]);

    echo "Test call created with ID: {$call->id}\n";

    // Test 4: Broadcast CallInitiated event
    echo "\n4. Broadcasting CallInitiated event...\n";

    try {
        broadcast(new CallInitiated($call, $conversation, $initiator))->toOthers();
        echo "✅ CallInitiated event broadcasted successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error broadcasting CallInitiated event: " . $e->getMessage() . "\n";
    }

    // Test 5: Manual event trigger
    echo "\n5. Testing manual event trigger...\n";

    $eventData = [
        'call_id' => $call->id,
        'call_type' => $call->call_type,
        'agora_channel_name' => $call->agora_channel_name,
        'initiator' => [
            'id' => $initiator->id,
            'name' => $initiator->name,
            'username' => $initiator->username,
            'profile_url' => $initiator->profile_url,
        ],
        'started_at' => $call->started_at->toISOString(),
    ];

    $manualResult = $pusher->trigger(
        'private-conversation.' . $conversation->id,
        'call.initiated',
        $eventData
    );

    echo "Manual trigger result: " . json_encode($manualResult) . "\n";

    // Test 6: Channel info
    echo "\n6. Getting channel info...\n";

    $channelName = 'private-conversation.' . $conversation->id;
    echo "Broadcasting to channel: {$channelName}\n";
    echo "Event name: call.initiated\n";

    // Clean up test call
    $call->delete();
    echo "\nTest call cleaned up.\n";

    echo "\n✅ All tests completed successfully!\n";
    echo "\nFor React Native developers:\n";
    echo "- Subscribe to channel: {$channelName}\n";
    echo "- Listen for event: call.initiated\n";
    echo "- Event data includes: call_id, call_type, agora_channel_name, initiator info\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
