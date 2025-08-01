<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\Stream;
use App\Models\User;
use App\Helpers\AgoraHelper;

echo "=== COMPLETE STREAMING DEBUG ===\n\n";

// Test 1: Check Agora Configuration
echo "1. Testing Agora Configuration:\n";
echo "--------------------------------\n";
try {
    $testResult = AgoraHelper::testTokenGeneration();
    echo "Agora Status: " . ($testResult['success'] ? 'WORKING' : 'FAILED') . "\n";
    echo "App ID: " . $testResult['app_id'] . "\n";
    echo "Token Generated: " . ($testResult['token'] ? 'YES' : 'NO') . "\n";
    if ($testResult['token']) {
        echo "Token Preview: " . $testResult['token_preview'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Agora Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check Live Streams
echo "2. Checking Live Streams:\n";
echo "-------------------------\n";
$liveStreams = Stream::where('status', 'live')->with('user')->get();
echo "Live Streams Count: " . $liveStreams->count() . "\n";

foreach ($liveStreams as $stream) {
    echo "Stream ID: {$stream->id}\n";
    echo "  Title: {$stream->title}\n";
    echo "  User: {$stream->user->name} (ID: {$stream->user->id})\n";
    echo "  Channel: {$stream->channel_name}\n";
    echo "  Status: {$stream->status}\n";
    echo "  Started: " . ($stream->started_at ? $stream->started_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  Deleted Flag: " . ($stream->deleted_flag ?? 'NULL') . "\n";
    echo "\n";
}

// Test 3: Create Test Stream and Token
echo "3. Creating Test Stream and Token:\n";
echo "----------------------------------\n";
try {
    $admin = User::first();
    $testStream = Stream::create([
        'user_id' => $admin->id,
        'title' => 'DEBUG Stream - ' . date('Y-m-d H:i:s'),
        'description' => 'Automated debug stream',
        'channel_name' => 'debug_stream_' . time(),
        'status' => 'upcoming',
        'is_paid' => false,
        'price' => 0,
        'currency' => 'USD',
        'free_minutes' => 60
    ]);

    echo "Created test stream: {$testStream->id}\n";

    // Start the stream
    $started = $testStream->start();
    echo "Stream start result: " . ($started ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Stream status after start: " . $testStream->fresh()->status . "\n";

    // Generate token
    $agoraUid = rand(100000, 999999);
    $token = AgoraHelper::generateRtcToken($testStream->channel_name, $agoraUid, 3600, 'publisher');
    echo "Token generated: " . ($token ? 'YES' : 'NO') . "\n";
    if ($token) {
        echo "Token preview: " . substr($token, 0, 50) . "...\n";
    }

    echo "\nTest URLs:\n";
    echo "Broadcast: http://localhost:8000/admin/streams/{$testStream->id}/broadcast\n";
    echo "Watch: http://localhost:8000/watch/{$admin->id}\n";
    echo "API Check: http://localhost:8000/api/streams/latest\n";

} catch (Exception $e) {
    echo "Error creating test stream: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
