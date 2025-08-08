<?php

require_once 'vendor/autoload.php';

// Load environment
$envFile = '.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"');
        }
    }
}

echo "=== Testing Call Direct Pusher Broadcasting ===\n\n";

// Check broadcasting configuration
echo "1. Broadcasting Configuration:\n";
echo "   BROADCAST_CONNECTION: " . ($_ENV['BROADCAST_CONNECTION'] ?? 'not set') . "\n";
echo "   PUSHER_APP_ID: " . ($_ENV['PUSHER_APP_ID'] ?? 'not set') . "\n";
echo "   PUSHER_APP_KEY: " . ($_ENV['PUSHER_APP_KEY'] ?? 'not set') . "\n";
echo "   PUSHER_APP_SECRET: " . (isset($_ENV['PUSHER_APP_SECRET']) ? 'set (hidden)' : 'not set') . "\n";
echo "   PUSHER_APP_CLUSTER: " . ($_ENV['PUSHER_APP_CLUSTER'] ?? 'not set') . "\n\n";

// Verify broadcasting is set to pusher
if (($_ENV['BROADCAST_CONNECTION'] ?? '') !== 'pusher') {
    echo "❌ ERROR: BROADCAST_CONNECTION should be 'pusher', current: " . ($_ENV['BROADCAST_CONNECTION'] ?? 'not set') . "\n";
    exit(1);
} else {
    echo "✅ Broadcasting driver: pusher\n";
}

// Test Pusher connectivity
echo "\n2. Testing Pusher Connectivity:\n";

if (empty($_ENV['PUSHER_APP_ID']) || empty($_ENV['PUSHER_APP_KEY']) || empty($_ENV['PUSHER_APP_SECRET'])) {
    echo "❌ Missing Pusher credentials\n";
    exit(1);
}

try {
    $pusher = new \Pusher\Pusher(
        $_ENV['PUSHER_APP_KEY'],
        $_ENV['PUSHER_APP_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        [
            'cluster' => $_ENV['PUSHER_APP_CLUSTER'] ?? 'eu',
            'useTLS' => true
        ]
    );

    // Test broadcast data similar to CallController
    $testData = [
        'call_id' => 999,
        'call_type' => 'audio',
        'agora_channel_name' => 'test_channel_' . time(),
        'initiator' => [
            'id' => 1,
            'name' => 'Test User',
            'profile_image' => null
        ],
        'conversation' => [
            'id' => 1,
            'type' => 'private'
        ],
        'started_at' => date('c')
    ];

    echo "   Broadcasting test CallInitiated event...\n";
    $result = $pusher->trigger('private-conversation.1', 'call.initiated', $testData);

    if ($result) {
        echo "   ✅ SUCCESS: Test CallInitiated event sent to Pusher!\n";
        echo "   📺 Channel: private-conversation.1\n";
        echo "   📡 Event: call.initiated\n";
        echo "   📋 Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";

        // Test CallAnswered event
        $answerData = [
            'call_id' => 999,
            'call_type' => 'audio',
            'agora_channel_name' => 'test_channel_' . time(),
            'answerer' => [
                'id' => 2,
                'name' => 'Test Answerer',
                'profile_image' => null
            ],
            'status' => 'connected',
            'connected_at' => date('c')
        ];

        echo "\n   Broadcasting test CallAnswered event...\n";
        $answerResult = $pusher->trigger('private-conversation.1', 'call.answered', $answerData);

        if ($answerResult) {
            echo "   ✅ SUCCESS: Test CallAnswered event sent to Pusher!\n";
            echo "   📺 Channel: private-conversation.1\n";
            echo "   📡 Event: call.answered\n";
        }

        // Test CallEnded event
        $endData = [
            'call_id' => 999,
            'call_type' => 'audio',
            'ended_by' => [
                'id' => 1,
                'name' => 'Test User',
                'profile_image' => null
            ],
            'status' => 'ended',
            'end_reason' => 'ended_by_caller',
            'duration' => 120,
            'formatted_duration' => '02:00',
            'ended_at' => date('c')
        ];

        echo "\n   Broadcasting test CallEnded event...\n";
        $endResult = $pusher->trigger('private-conversation.1', 'call.ended', $endData);

        if ($endResult) {
            echo "   ✅ SUCCESS: Test CallEnded event sent to Pusher!\n";
            echo "   📺 Channel: private-conversation.1\n";
            echo "   📡 Event: call.ended\n";
        }

    } else {
        echo "   ❌ FAILED: Could not send test event to Pusher\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n3. Next Steps:\n";
echo "   • Test call initiation: POST /api/v1/calls/initiate\n";
echo "   • Monitor Pusher debug console: https://dashboard.pusher.com/apps/{$_ENV['PUSHER_APP_ID']}/console\n";
echo "   • Subscribe to 'private-conversation.{conversation_id}' channels\n";
echo "   • Listen for 'call.initiated', 'call.answered', 'call.ended', 'call.missed' events\n\n";

echo "🎉 Call Direct Pusher Broadcasting Test Complete!\n";
echo "CallController now uses direct Pusher broadcasting like MessageController.\n";
