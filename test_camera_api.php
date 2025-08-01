<?php

// Simple API test for camera endpoints
echo "Testing Camera Management API\n";
echo "====================================\n\n";

// Test data
$testStreamId = 1; // Assuming there's a stream with ID 1
$testCameraData = [
    'camera_name' => 'Test Camera API',
    'device_type' => 'webcam',
    'resolution' => '720p',
    'device_id' => 'test-device-api-123'
];

// Get CSRF token (simulate)
$csrfToken = 'test-token'; // In real scenario, this would be fetched from the page

// Test GET cameras endpoint
echo "1. Testing GET /admin/api/streams/$testStreamId/cameras\n";
$getCamerasUrl = "http://127.0.0.1:8000/admin/api/streams/$testStreamId/cameras";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $getCamerasUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: " . ($response ?: 'No response') . "\n\n";

// Test POST cameras endpoint
echo "2. Testing POST /admin/api/streams/$testStreamId/cameras\n";
$postCamerasUrl = "http://127.0.0.1:8000/admin/api/streams/$testStreamId/cameras";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $postCamerasUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testCameraData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: " . ($response ?: 'No response') . "\n\n";

echo "Test completed. Check the responses above.\n";
echo "Note: CSRF token validation may cause 419 errors in this test.\n";
echo "The actual frontend will have proper CSRF tokens.\n";
