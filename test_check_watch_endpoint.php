<?php

// Simple test for the check-watch endpoint
echo "Testing check-watch endpoint...\n";

// Test 1: Unprotected endpoint (for stream ID 15)
echo "\n=== Testing unprotected endpoint: streams/15/check-watch ===\n";

$url = 'http://127.0.0.1:8000/api/v1/streams/15/check-watch';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

// Test 2: Check if we have any streams in database first
echo "\n=== Checking available streams ===\n";
require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $streams = \App\Models\Stream::select('id', 'title', 'status', 'is_paid', 'price', 'currency', 'free_minutes')
                                ->take(5)
                                ->get();

    if ($streams->count() > 0) {
        echo "Available streams:\n";
        foreach ($streams as $stream) {
            echo "- ID: {$stream->id}, Title: {$stream->title}, Status: {$stream->status}, ";
            echo "Paid: " . ($stream->is_paid ? 'Yes' : 'No');
            if ($stream->is_paid) {
                echo " (Price: {$stream->currency} {$stream->price}, Free Minutes: {$stream->free_minutes})";
            }
            echo "\n";
        }

        // Test with a real stream ID
        $firstStream = $streams->first();
        echo "\n=== Testing with real stream ID: {$firstStream->id} ===\n";

        $url = "http://127.0.0.1:8000/api/v1/streams/{$firstStream->id}/check-watch";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "HTTP Status Code: $httpCode\n";
        echo "Response:\n";
        echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

    } else {
        echo "No streams found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
