<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Stream;
use App\Models\StreamCamera;

// Create a test to verify the camera save functionality
try {
    // First, find an existing stream or create one for testing
    $stream = Stream::first();

    if (!$stream) {
        echo "No streams found in database. Cannot test camera save.\n";
        exit(1);
    }

    echo "Testing camera save with stream ID: " . $stream->id . "\n";

    // Try to create a camera with the new device_id field
    $cameraData = [
        'stream_id' => $stream->id,
        'camera_name' => 'Test Camera',
        'device_type' => 'webcam',
        'device_id' => 'test-device-123',
        'is_active' => true,
        'is_primary' => false,
    ];

    $camera = StreamCamera::create($cameraData);

    if ($camera) {
        echo "✅ Camera created successfully!\n";
        echo "Camera ID: " . $camera->id . "\n";
        echo "Camera Name: " . $camera->camera_name . "\n";
        echo "Device ID: " . $camera->device_id . "\n";

        // Clean up - delete the test camera
        $camera->delete();
        echo "✅ Test camera cleaned up.\n";
    } else {
        echo "❌ Failed to create camera.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
