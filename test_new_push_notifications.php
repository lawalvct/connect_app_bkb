<?php

/**
 * Test script for new Firebase & Expo push notification setup
 * Tests both FCM and Expo token handling
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FirebaseService;
use App\Services\ExpoNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "===========================================\n";
echo "  Push Notification Test - New Setup\n";
echo "===========================================\n\n";

// Check Firebase configuration
echo "1️⃣  Checking Firebase Configuration...\n";
echo "   Project ID: " . config('services.firebase.project_id') . "\n";
echo "   Server Key: " . (config('services.firebase.server_key') ? '✓ Set' : '✗ NOT SET') . "\n";
echo "   Credentials Path: " . config('services.firebase.credentials_path') . "\n";
echo "   API Key: " . substr(config('services.firebase.api_key'), 0, 20) . "...\n";
echo "   Messaging Sender ID: " . config('services.firebase.messaging_sender_id') . "\n\n";

// Check if credentials file exists
$credPath = storage_path('app/firebase-credentials.json');
if (file_exists($credPath)) {
    $creds = json_decode(file_get_contents($credPath), true);
    echo "   Service Account Project: " . ($creds['project_id'] ?? 'N/A') . "\n";
    if (isset($creds['project_id'])) {
        if ($creds['project_id'] === 'connect-app-efa83') {
            echo "   ✓ Credentials are for the NEW project (connect-app-efa83)\n";
        } else {
            echo "   ⚠️  WARNING: Credentials are for OLD project ({$creds['project_id']})\n";
            echo "   → Please download new credentials from Firebase Console\n";
        }
    }
} else {
    echo "   ✗ Credentials file NOT FOUND at: $credPath\n";
}
echo "\n";

// Test Expo token detection
echo "2️⃣  Testing Expo Token Detection...\n";
$testExpoToken = 'ExponentPushToken[1b4NasOze1qsCSXIH6jl9n]';
$isExpoToken = ExpoNotificationService::isExpoPushToken($testExpoToken);
echo "   Test Token: $testExpoToken\n";
echo "   Detected as Expo Token: " . ($isExpoToken ? '✓ YES' : '✗ NO') . "\n\n";

// Test FCM token detection (should not be detected as Expo)
echo "3️⃣  Testing FCM Token Detection...\n";
$testFcmToken = 'eGu7:APA91bFxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$isFcmExpoToken = ExpoNotificationService::isExpoPushToken($testFcmToken);
echo "   Test Token: $testFcmToken\n";
echo "   Detected as Expo Token: " . ($isFcmExpoToken ? '✗ YES (ERROR!)' : '✓ NO (Correct)') . "\n\n";

// Check for existing tokens in database
echo "4️⃣  Checking Database for Push Tokens...\n";
$expoTokens = DB::table('user_fcm_tokens')
    ->where('fcm_token', 'LIKE', 'ExponentPushToken%')
    ->where('is_active', true)
    ->get();

$fcmTokens = DB::table('user_fcm_tokens')
    ->where('fcm_token', 'NOT LIKE', 'ExponentPushToken%')
    ->where('is_active', true)
    ->get();

echo "   Expo Tokens Found: " . $expoTokens->count() . "\n";
echo "   FCM Tokens Found: " . $fcmTokens->count() . "\n\n";

if ($expoTokens->count() > 0) {
    echo "   📱 Expo Tokens:\n";
    foreach ($expoTokens->take(5) as $token) {
        echo "      User ID: {$token->user_id}, Token: " . substr($token->fcm_token, 0, 40) . "...\n";
    }
    echo "\n";
}

if ($fcmTokens->count() > 0) {
    echo "   📱 FCM Tokens:\n";
    foreach ($fcmTokens->take(5) as $token) {
        echo "      User ID: {$token->user_id}, Token: " . substr($token->fcm_token, 0, 40) . "...\n";
    }
    echo "\n";
}

// Test sending notification (dry run)
echo "5️⃣  Testing Notification Routing (Dry Run)...\n\n";

// Test with a real token if available
$testToken = null;
$testUserId = null;

if ($expoTokens->count() > 0) {
    $testToken = $expoTokens->first()->fcm_token;
    $testUserId = $expoTokens->first()->user_id;
    echo "   Using Expo Token for Test\n";
} elseif ($fcmTokens->count() > 0) {
    $testToken = $fcmTokens->first()->fcm_token;
    $testUserId = $fcmTokens->first()->user_id;
    echo "   Using FCM Token for Test\n";
}

if ($testToken) {
    echo "   Token: " . substr($testToken, 0, 40) . "...\n";
    echo "   User ID: $testUserId\n\n";

    echo "   Attempting to send test notification...\n";

    try {
        $firebaseService = new FirebaseService();
        $result = $firebaseService->sendNotification(
            $testToken,
            'Test Notification - New Setup',
            'This is a test notification from the updated Firebase configuration.',
            [
                'type' => 'test',
                'test_id' => uniqid(),
                'timestamp' => now()->toIso8601String()
            ],
            $testUserId
        );

        if ($result) {
            echo "   ✅ Notification sent successfully!\n";
            echo "   Check push_notification_logs table for details.\n";
        } else {
            echo "   ⚠️  Notification sending failed.\n";
            echo "   Check push_notification_logs table for error details.\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️  No tokens found in database.\n";
    echo "   To test:\n";
    echo "   1. Login with mobile app to register a token\n";
    echo "   2. Or manually insert a test token\n";
}

echo "\n";

// Check recent notification logs
echo "6️⃣  Recent Notification Logs...\n";
$recentLogs = DB::table('push_notification_logs')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentLogs->count() > 0) {
    foreach ($recentLogs as $log) {
        $status = $log->status === 'sent' ? '✅' : '❌';
        echo "   $status {$log->created_at} - User: {$log->user_id} - Status: {$log->status}\n";
        if ($log->error_message) {
            echo "      Error: {$log->error_message}\n";
        }
    }
} else {
    echo "   No notification logs found.\n";
}

echo "\n";

// Summary and next steps
echo "===========================================\n";
echo "  Summary & Next Steps\n";
echo "===========================================\n\n";

$issues = [];

// Check configuration issues
if (config('services.firebase.server_key') === 'NEEDS_TO_BE_GENERATED_FROM_FIREBASE_CONSOLE') {
    $issues[] = "Firebase Server Key not set in .env";
}

if (!file_exists($credPath)) {
    $issues[] = "Firebase credentials file not found";
} elseif (isset($creds['project_id']) && $creds['project_id'] !== 'connect-app-efa83') {
    $issues[] = "Firebase credentials are for old project";
}

if (config('services.firebase.vapid_key') === 'NEEDS_TO_BE_GENERATED_FROM_FIREBASE_CONSOLE') {
    $issues[] = "VAPID Key not set in .env (needed for web push)";
}

if (count($issues) > 0) {
    echo "⚠️  Configuration Issues Found:\n\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". $issue\n";
    }
    echo "\n";
    echo "📝 To fix these issues:\n";
    echo "   1. Go to Firebase Console: https://console.firebase.google.com/project/connect-app-efa83\n";
    echo "   2. Generate required keys (see FIREBASE_PUSH_NOTIFICATION_SETUP_NEW.md)\n";
    echo "   3. Update .env file\n";
    echo "   4. Run this test again\n\n";
} else {
    echo "✅ Configuration looks good!\n\n";

    if ($expoTokens->count() > 0 || $fcmTokens->count() > 0) {
        echo "✅ Push tokens found in database\n";
        echo "✅ System is ready to send notifications\n\n";
    } else {
        echo "⚠️  No push tokens in database yet.\n";
        echo "   Have users login to register their tokens.\n\n";
    }
}

echo "📖 For detailed setup instructions, see:\n";
echo "   → FIREBASE_PUSH_NOTIFICATION_SETUP_NEW.md\n\n";

echo "===========================================\n\n";
