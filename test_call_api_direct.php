<?php

// Simple test to call the API endpoint directly
echo "=== Testing Call API Endpoint ===\n\n";

// API endpoint
$url = 'http://localhost/connect_app_backend_new-1/public/api/v1/calls/initiate';

// Test data
$data = [
    'conversation_id' => 1,
    'call_type' => 'audio'
];

// Get admin token from a test script
$tokenScript = 'create_test_fcm_token.php';
if (file_exists($tokenScript)) {
    echo "📱 Getting test token...\n";
    ob_start();
    include $tokenScript;
    $output = ob_get_clean();

    // Extract token from the output
    if (preg_match('/Token: (.+)/', $output, $matches)) {
        $token = trim($matches[1]);
        echo "✅ Got token: " . substr($token, 0, 20) . "...\n\n";

        // Make the API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        echo "🚀 Making API call to: $url\n";
        echo "📊 Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "❌ cURL Error: $error\n";
        } else {
            echo "📱 Response (HTTP $httpCode):\n";
            $responseData = json_decode($response, true);
            if ($responseData) {
                echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";

                if ($httpCode === 200 || $httpCode === 201) {
                    echo "✅ SUCCESS: Call initiated successfully!\n";
                    if (isset($responseData['data']['call'])) {
                        $callData = $responseData['data']['call'];
                        echo "   Call ID: {$callData['id']}\n";
                        echo "   Channel: {$callData['agora_channel_name']}\n";
                    }
                    echo "\n📡 Check Pusher debug console for 'call.initiated' event!\n";
                    echo "   URL: https://dashboard.pusher.com/apps/1471502/console\n";
                } else {
                    echo "❌ API call failed with HTTP $httpCode\n";
                }
            } else {
                echo "Raw response: $response\n";
            }
        }

    } else {
        echo "❌ Could not extract token from test script\n";
    }
} else {
    echo "❌ Test token script not found: $tokenScript\n";
}

echo "\n🏁 API Test Complete!\n";
