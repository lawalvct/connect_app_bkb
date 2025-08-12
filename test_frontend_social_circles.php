<?php

echo "=== Testing Frontend Social Circles Request ===\n";

try {
    // Check if we need to simulate authentication
    echo "Testing without authentication first...\n";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost:8000/admin/api/social-circles',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ],
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://stdout', 'w')
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "\n--- Response ---\n";
    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
    echo "Response Body:\n$response\n";

    // Try to decode as JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "\nParsed JSON:\n";
        print_r($data);
    } else {
        echo "\nNot valid JSON or empty response\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
