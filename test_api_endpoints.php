<?php
/**
 * Simple test script to verify API endpoints are working
 */

echo "=== Laravel API Test Script ===\n";
echo "Testing API endpoints...\n\n";

$baseUrl = 'http://localhost:8000';

// Test 1: Debug route
echo "1. Testing debug route:\n";
try {
    $response = file_get_contents("$baseUrl/api/v1/debug-routes");
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✅ Debug route working: " . $data['message'] . "\n";
    } else {
        echo "❌ Debug route failed\n";
    }
} catch (Exception $e) {
    echo "❌ Debug route error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Registration route with empty data (should get 422)
echo "2. Testing registration route (empty data):\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => json_encode([])
    ]
]);

try {
    $response = @file_get_contents("$baseUrl/api/v1/register", false, $context);
    if ($response) {
        $data = json_decode($response, true);
        echo "✅ Registration route accessible\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        // Check if it's a 422 error (which is expected)
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, '422') !== false) {
                    echo "✅ Registration route working (422 validation error as expected)\n";
                    break;
                }
                if (strpos($header, '405') !== false) {
                    echo "❌ Still getting 405 Method Not Allowed\n";
                    break;
                }
            }
        } else {
            echo "❌ Registration route failed\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Registration route error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: OPTIONS request
echo "3. Testing CORS OPTIONS request:\n";
$optionsContext = stream_context_create([
    'http' => [
        'method' => 'OPTIONS',
        'header' => [
            'Origin: http://localhost:3000',
            'Access-Control-Request-Method: POST',
            'Access-Control-Request-Headers: Content-Type'
        ]
    ]
]);

try {
    $response = @file_get_contents("$baseUrl/api/v1/register", false, $optionsContext);
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (strpos($header, '200') !== false) {
                echo "✅ CORS OPTIONS request working\n";
                break;
            }
        }

        // Show CORS headers
        echo "CORS Headers found:\n";
        foreach ($http_response_header as $header) {
            if (stripos($header, 'access-control') !== false) {
                echo "  $header\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ CORS OPTIONS error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Open browser_postman_test.html in your browser for interactive testing.\n";
