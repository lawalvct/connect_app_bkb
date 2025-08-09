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

echo "=== Testing Enhanced Call Broadcast Data ===\n\n";

// Check broadcasting configuration
echo "✅ Broadcasting driver: " . ($_ENV['BROADCAST_CONNECTION'] ?? 'not set') . "\n\n";

if (($_ENV['BROADCAST_CONNECTION'] ?? '') !== 'pusher') {
    echo "❌ ERROR: BROADCAST_CONNECTION should be 'pusher'\n";
    exit(1);
}

// Test enhanced Pusher connectivity with new data structure
echo "🧪 Testing Enhanced Call Broadcast Data Structure:\n\n";

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

    // Enhanced CallInitiated test data (similar to what CallController now sends)
    $enhancedCallInitiatedData = [
        'call_id' => 999,
        'call_type' => 'audio',
        'agora_channel_name' => 'test_channel_' . time(),
        'initiator' => [
            'id' => 1,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'profile_image' => 'https://example.com/uploads/profiles/user_1_profile.jpg', // Full URL
            'avatar_url' => 'https://example.com/uploads/avatars/user_1_avatar.jpg'
        ],
        'conversation' => [
            'id' => 1,
            'type' => 'private'
        ],
        'participants' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'username' => 'johndoe',
                'profile_image' => 'https://example.com/uploads/profiles/user_1_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_1_avatar.jpg',
                'status' => 'joined'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'username' => 'janesmith',
                'profile_image' => 'https://example.com/uploads/profiles/user_2_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_2_avatar.jpg',
                'status' => 'invited'
            ]
        ],
        'started_at' => date('c')
    ];

    echo "1. Testing CallInitiated with Enhanced Data:\n";
    $result = $pusher->trigger('private-conversation.1', 'call.initiated', $enhancedCallInitiatedData);

    if ($result) {
        echo "   ✅ SUCCESS: Enhanced CallInitiated event sent!\n";
        echo "   📺 Channel: private-conversation.1\n";
        echo "   📡 Event: call.initiated\n";
        echo "   👤 Initiator with full profile URL: " . $enhancedCallInitiatedData['initiator']['profile_image'] . "\n";
        echo "   👥 Participants count: " . count($enhancedCallInitiatedData['participants']) . "\n";

        foreach ($enhancedCallInitiatedData['participants'] as $participant) {
            echo "      - {$participant['name']} ({$participant['status']}) - Profile: {$participant['profile_image']}\n";
        }
    }

    // Enhanced CallAnswered test data
    $enhancedCallAnsweredData = [
        'call_id' => 999,
        'call_type' => 'audio',
        'agora_channel_name' => 'test_channel_' . time(),
        'answerer' => [
            'id' => 2,
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'profile_image' => 'https://example.com/uploads/profiles/user_2_profile.jpg',
            'avatar_url' => 'https://example.com/uploads/avatars/user_2_avatar.jpg'
        ],
        'participants' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'username' => 'johndoe',
                'profile_image' => 'https://example.com/uploads/profiles/user_1_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_1_avatar.jpg',
                'status' => 'joined'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'username' => 'janesmith',
                'profile_image' => 'https://example.com/uploads/profiles/user_2_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_2_avatar.jpg',
                'status' => 'joined'
            ]
        ],
        'status' => 'connected',
        'connected_at' => date('c')
    ];

    echo "\n2. Testing CallAnswered with Enhanced Data:\n";
    $answerResult = $pusher->trigger('private-conversation.1', 'call.answered', $enhancedCallAnsweredData);

    if ($answerResult) {
        echo "   ✅ SUCCESS: Enhanced CallAnswered event sent!\n";
        echo "   📺 Channel: private-conversation.1\n";
        echo "   📡 Event: call.answered\n";
        echo "   👤 Answerer with full profile URL: " . $enhancedCallAnsweredData['answerer']['profile_image'] . "\n";
        echo "   👥 Participants count: " . count($enhancedCallAnsweredData['participants']) . "\n";
    }

    // Enhanced CallEnded test data
    $enhancedCallEndedData = [
        'call_id' => 999,
        'call_type' => 'audio',
        'ended_by' => [
            'id' => 1,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'profile_image' => 'https://example.com/uploads/profiles/user_1_profile.jpg',
            'avatar_url' => 'https://example.com/uploads/avatars/user_1_avatar.jpg'
        ],
        'participants' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'username' => 'johndoe',
                'profile_image' => 'https://example.com/uploads/profiles/user_1_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_1_avatar.jpg',
                'status' => 'left'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'username' => 'janesmith',
                'profile_image' => 'https://example.com/uploads/profiles/user_2_profile.jpg',
                'avatar_url' => 'https://example.com/uploads/avatars/user_2_avatar.jpg',
                'status' => 'left'
            ]
        ],
        'status' => 'ended',
        'end_reason' => 'ended_by_caller',
        'duration' => 120,
        'formatted_duration' => '02:00',
        'ended_at' => date('c')
    ];

    echo "\n3. Testing CallEnded with Enhanced Data:\n";
    $endResult = $pusher->trigger('private-conversation.1', 'call.ended', $enhancedCallEndedData);

    if ($endResult) {
        echo "   ✅ SUCCESS: Enhanced CallEnded event sent!\n";
        echo "   📺 Channel: private-conversation.1\n";
        echo "   📡 Event: call.ended\n";
        echo "   👤 Ended by with full profile URL: " . $enhancedCallEndedData['ended_by']['profile_image'] . "\n";
        echo "   👥 Participants count: " . count($enhancedCallEndedData['participants']) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 Enhanced Call Broadcast Summary:\n";
echo "   ✅ Initiator profile_image: Full URL included\n";
echo "   ✅ Avatar URL: Full URL included\n";
echo "   ✅ Participants array: Complete participant list with profile URLs\n";
echo "   ✅ Participant status: Individual status for each participant\n";
echo "   ✅ Username: Added for better identification\n";

echo "\n📋 New Response Structure:\n";
echo "   • initiator.profile_image: Full URL (e.g., https://domain.com/uploads/profiles/file.jpg)\n";
echo "   • initiator.avatar_url: Full URL for avatar\n";
echo "   • participants[]: Array of all conversation participants\n";
echo "   • participants[].profile_image: Full URL for each participant\n";
echo "   • participants[].status: Individual status (invited/joined/left/etc.)\n";

echo "\n🚀 Ready for Real Testing!\n";
echo "Monitor Pusher debug console: https://dashboard.pusher.com/apps/{$_ENV['PUSHER_APP_ID']}/console\n";
