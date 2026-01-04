<?php

/**
 * Diagnostic script to test Firebase Cloud Messaging API
 * Tests both OAuth token generation and FCM API access
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "===========================================\n";
echo "  Firebase FCM API Diagnostic Test\n";
echo "===========================================\n\n";

// Load credentials
$credentialsPath = storage_path('app/firebase-credentials.json');
echo "1️⃣  Loading credentials from: $credentialsPath\n\n";

if (!file_exists($credentialsPath)) {
    echo "❌ ERROR: Credentials file not found!\n";
    exit(1);
}

$credentials = json_decode(file_get_contents($credentialsPath), true);
echo "   Project ID: " . $credentials['project_id'] . "\n";
echo "   Client Email: " . $credentials['client_email'] . "\n\n";

// Verify it's the correct project
if ($credentials['project_id'] !== 'connect-app-efa83') {
    echo "⚠️  WARNING: Project ID mismatch! Expected 'connect-app-efa83'\n\n";
}

// Generate JWT for OAuth
echo "2️⃣  Generating OAuth JWT...\n";
$jwtHeader = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
$now = time();
$jwtClaimSet = rtrim(strtr(base64_encode(json_encode([
    'iss' => $credentials['client_email'],
    'scope' => 'https://www.googleapis.com/auth/firebase.messaging https://www.googleapis.com/auth/cloud-platform',
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $now,
    'exp' => $now + 3600,
])), '+/', '-_'), '=');

$jwtToSign = $jwtHeader . '.' . $jwtClaimSet;

// Sign the JWT
$privateKey = $credentials['private_key'];
openssl_sign($jwtToSign, $signature, $privateKey, 'sha256');
$jwt = $jwtToSign . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

echo "   ✓ JWT generated successfully\n\n";

// Get OAuth access token
echo "3️⃣  Requesting OAuth access token...\n";
$tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt,
]);

$tokenData = $tokenResponse->json();

if (!$tokenResponse->successful() || !isset($tokenData['access_token'])) {
    echo "❌ ERROR: Failed to get access token!\n";
    echo "   Status: " . $tokenResponse->status() . "\n";
    echo "   Response: " . json_encode($tokenData, JSON_PRETTY_PRINT) . "\n\n";
    exit(1);
}

$accessToken = $tokenData['access_token'];
echo "   ✓ Access token obtained successfully\n";
echo "   Token type: " . ($tokenData['token_type'] ?? 'N/A') . "\n";
echo "   Expires in: " . ($tokenData['expires_in'] ?? 'N/A') . " seconds\n\n";

// Check if FCM API is enabled
echo "4️⃣  Checking Firebase Cloud Messaging API status...\n";
$apiCheckUrl = "https://serviceusage.googleapis.com/v1/projects/{$credentials['project_id']}/services/fcm.googleapis.com";

$apiCheckResponse = Http::withToken($accessToken)->get($apiCheckUrl);
$apiCheckData = $apiCheckResponse->json();

if ($apiCheckResponse->successful()) {
    $state = $apiCheckData['state'] ?? 'UNKNOWN';
    echo "   FCM API State: $state\n";
    if ($state === 'ENABLED') {
        echo "   ✓ FCM API is ENABLED\n\n";
    } else {
        echo "   ⚠️  FCM API is NOT ENABLED!\n";
        echo "   → Please enable it at: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project={$credentials['project_id']}\n\n";
    }
} else {
    echo "   ⚠️  Could not check API status\n";
    echo "   Status: " . $apiCheckResponse->status() . "\n";
    echo "   Response: " . json_encode($apiCheckData, JSON_PRETTY_PRINT) . "\n\n";
}

// Test FCM v1 API with a dry-run (validate only)
echo "5️⃣  Testing FCM v1 API endpoint...\n";
$fcmUrl = "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send";

// Use validate_only to test without actually sending
$testMessage = [
    'validate_only' => true,  // This tests the API without sending a real notification
    'message' => [
        'token' => 'test_token_for_validation',
        'notification' => [
            'title' => 'Test',
            'body' => 'Test message'
        ]
    ]
];

$fcmResponse = Http::withToken($accessToken)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post($fcmUrl, $testMessage);

$fcmData = $fcmResponse->json();

echo "   FCM URL: $fcmUrl\n";
echo "   Status: " . $fcmResponse->status() . "\n";

if ($fcmResponse->status() === 200) {
    echo "   ✓ FCM API is working!\n\n";
} elseif ($fcmResponse->status() === 400 && isset($fcmData['error']['details'])) {
    // 400 with "invalid token" means API is working, just token is fake (expected)
    $errorMessage = $fcmData['error']['message'] ?? '';
    if (strpos($errorMessage, 'not a valid FCM registration token') !== false ||
        strpos($errorMessage, 'Invalid registration token') !== false) {
        echo "   ✓ FCM API is working! (Token validation failed as expected for test token)\n\n";
    } else {
        echo "   ⚠️  Unexpected 400 error:\n";
        echo "   " . json_encode($fcmData, JSON_PRETTY_PRINT) . "\n\n";
    }
} elseif ($fcmResponse->status() === 403) {
    echo "   ❌ PERMISSION DENIED!\n\n";
    echo "   Error Details:\n";
    echo "   " . json_encode($fcmData, JSON_PRETTY_PRINT) . "\n\n";

    echo "   🔧 SOLUTIONS:\n";
    echo "   1. Make sure Firebase Cloud Messaging API is enabled:\n";
    echo "      → https://console.cloud.google.com/apis/library/fcm.googleapis.com?project={$credentials['project_id']}\n\n";

    echo "   2. Check service account roles in IAM:\n";
    echo "      → https://console.cloud.google.com/iam-admin/iam?project={$credentials['project_id']}\n";
    echo "      → Required role: 'Firebase Cloud Messaging Admin'\n\n";

    echo "   3. Wait 5-10 minutes for permission changes to propagate\n\n";

    echo "   4. Try regenerating the service account key:\n";
    echo "      → Go to Firebase Console > Project Settings > Service Accounts\n";
    echo "      → Generate new private key\n";
    echo "      → Replace storage/app/firebase-credentials.json\n\n";

} elseif ($fcmResponse->status() === 404) {
    echo "   ❌ API NOT FOUND (404)!\n\n";
    echo "   The Firebase Cloud Messaging API is not enabled.\n";
    echo "   → Enable it at: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project={$credentials['project_id']}\n\n";
} else {
    echo "   ⚠️  Unexpected response:\n";
    echo "   " . json_encode($fcmData, JSON_PRETTY_PRINT) . "\n\n";
}

// Summary
echo "===========================================\n";
echo "  Summary\n";
echo "===========================================\n\n";

echo "Project: {$credentials['project_id']}\n";
echo "Service Account: {$credentials['client_email']}\n";
echo "OAuth Token: " . ($accessToken ? "✓ Working" : "✗ Failed") . "\n";
echo "FCM API: " . ($fcmResponse->status() === 200 || ($fcmResponse->status() === 400 && strpos($fcmData['error']['message'] ?? '', 'token') !== false) ? "✓ Working" : "✗ Not working (Status: {$fcmResponse->status()})") . "\n\n";

if ($fcmResponse->status() === 403) {
    echo "⚠️  NEXT STEPS:\n";
    echo "   1. Go to: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project={$credentials['project_id']}\n";
    echo "   2. Click 'ENABLE' if not already enabled\n";
    echo "   3. Wait 5-10 minutes for propagation\n";
    echo "   4. Run this script again to verify\n\n";

    echo "   If still failing after API is enabled:\n";
    echo "   - Generate a NEW service account key from Firebase Console\n";
    echo "   - Replace storage/app/firebase-credentials.json with the new file\n";
    echo "   - Run php artisan config:clear\n";
    echo "   - Run this script again\n\n";
}

echo "===========================================\n\n";
