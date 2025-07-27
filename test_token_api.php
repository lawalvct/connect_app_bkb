<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Stream;
use App\Helpers\AgoraHelper;
use App\Models\StreamViewer;

// Create Laravel application instance
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing Agora token generation directly...\n";

    // Get first stream
    $stream = Stream::first();
    if (!$stream) {
        echo "No streams found in database\n";
        exit(1);
    }

    echo "Testing with stream: " . $stream->id . " - " . $stream->title . "\n";
    echo "Channel name: " . $stream->channel_name . "\n";

    // Test UID generation
    $uid = StreamViewer::generateAgoraUid();
    echo "Generated UID: " . $uid . "\n";

    // Check what Agora classes are available
    echo "\nChecking Agora token builder classes:\n";
    $possibleClasses = [
        'BoogieFromZk\AgoraToken\RtcTokenBuilder',
        'BoogieFromZk\AgoraToken\RtcTokenBuilder2',
        'RtcTokenBuilder',
        'RtcTokenBuilder2',
        '\RtcTokenBuilder',
        '\RtcTokenBuilder2',
        'AgoraTools\RtcTokenBuilder',
        'Agora\RtcTokenBuilder'
    ];

    foreach ($possibleClasses as $className) {
        if (class_exists($className)) {
            echo "✓ Found class: " . $className . "\n";
            $reflection = new ReflectionClass($className);
            $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);
            echo "  Methods: " . implode(', ', array_map(function($m) { return $m->getName(); }, $methods)) . "\n";
        } else {
            echo "✗ Class not found: " . $className . "\n";
        }
    }

    // Test token generation
    echo "\nTesting token generation...\n";
    echo "App ID: " . env('AGORA_APP_ID') . "\n";
    echo "App Certificate: " . substr(env('AGORA_APP_CERTIFICATE'), 0, 10) . "...\n";

    $token = AgoraHelper::generateRtcToken(
        $stream->channel_name,
        (int)$uid,
        3600,
        'publisher'
    );

    if ($token) {
        echo "✓ Token generated successfully!\n";
        echo "Token length: " . strlen($token) . "\n";
        echo "Token starts with: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "✗ Failed to generate token\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
