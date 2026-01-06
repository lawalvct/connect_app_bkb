<?php

/**
 * Check user's FCM tokens and validate them
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "===========================================\n";
echo "  Check User FCM Tokens\n";
echo "===========================================\n\n";

// Get user ID from command line argument
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "Usage: php check_user_tokens.php <user_id>\n";
    echo "Example: php check_user_tokens.php 3114\n\n";
    exit(1);
}

echo "Checking tokens for User ID: $userId\n\n";

// Get user info
$user = DB::table('users')->where('id', $userId)->first();

if (!$user) {
    echo "❌ User not found!\n\n";
    exit(1);
}

echo "👤 User Info:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->first_name} {$user->last_name}\n";
echo "   Email: {$user->email}\n\n";

// Get all tokens for this user
$allTokens = DB::table('user_fcm_tokens')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

echo "📱 All FCM Tokens (Total: " . $allTokens->count() . "):\n\n";

if ($allTokens->count() === 0) {
    echo "   ⚠️  No tokens found for this user!\n";
    echo "   User needs to login on mobile app to register a token.\n\n";
    exit(0);
}

foreach ($allTokens as $index => $token) {
    $num = $index + 1;
    $status = $token->is_active ? '✅ Active' : '❌ Inactive';
    $tokenType = strpos($token->fcm_token, 'ExponentPushToken') === 0 ? 'Expo' : 'FCM';

    echo "   Token #{$num}:\n";
    echo "   Status: $status\n";
    echo "   Type: $tokenType\n";
    echo "   Token: " . substr($token->fcm_token, 0, 50) . "...\n";
    echo "   Device: " . ($token->device_type ?? 'Unknown') . "\n";
    echo "   Created: {$token->created_at}\n";
    echo "   Last Used: " . ($token->last_used_at ?? 'Never') . "\n";

    if ($token->is_active) {
        // Check if this is an Expo token
        if ($tokenType === 'Expo') {
            echo "   ✓ Token format: Valid Expo token\n";
            echo "   ℹ️  Will be sent via Expo Push API\n";
        } else {
            echo "   ✓ Token format: Valid FCM token\n";
            echo "   ℹ️  Will be sent via Firebase v1 API\n";
        }
    } else {
        echo "   ⚠️  This token is INACTIVE and won't receive notifications\n";
    }

    echo "\n";
}

// Get active tokens
$activeTokens = $allTokens->where('is_active', true);

echo "===========================================\n";
echo "  Summary\n";
echo "===========================================\n\n";

echo "Total Tokens: " . $allTokens->count() . "\n";
echo "Active Tokens: " . $activeTokens->count() . "\n";
echo "Inactive Tokens: " . ($allTokens->count() - $activeTokens->count()) . "\n\n";

if ($activeTokens->count() === 0) {
    echo "❌ PROBLEM: No active tokens!\n\n";
    echo "🔧 SOLUTION:\n";
    echo "   1. User needs to login again on mobile app\n";
    echo "   2. App should automatically register a new token\n";
    echo "   3. Make sure mobile app is using NEW Firebase config:\n";
    echo "      - Sender ID: 1075408006474\n";
    echo "      - Project: connect-app-efa83\n\n";
} else {
    echo "✅ Active tokens found!\n\n";

    // Check when tokens were created
    $oldestActive = $activeTokens->sortBy('created_at')->first();
    $newestActive = $activeTokens->sortByDesc('created_at')->first();

    echo "📅 Token Registration:\n";
    echo "   Oldest active: {$oldestActive->created_at}\n";
    echo "   Newest active: {$newestActive->created_at}\n\n";

    // Check if tokens are old (created before today)
    $today = date('Y-m-d');
    $tokenDate = date('Y-m-d', strtotime($newestActive->created_at));

    if ($tokenDate < $today) {
        echo "⚠️  WARNING: Tokens were created before today!\n";
        echo "   If you recently switched Firebase projects, these tokens\n";
        echo "   might be from the OLD project and won't work.\n\n";
        echo "🔧 SOLUTION:\n";
        echo "   1. Run: php clear_old_fcm_tokens.php\n";
        echo "   2. Have user login again to register new token\n\n";
    } else {
        echo "✅ Tokens look recent and should work!\n\n";

        echo "🔍 If notifications still don't arrive, check:\n";
        echo "   1. Mobile app notifications permission is enabled\n";
        echo "   2. App is running or in background (not force-closed)\n";
        echo "   3. Device has internet connection\n";
        echo "   4. Check Laravel logs: tail -f storage/logs/laravel.log\n";
        echo "   5. For Expo: Check Expo push tool at https://expo.dev/notifications\n\n";
    }
}

// Check recent notification logs for this user
echo "📊 Recent Push Notification Logs:\n\n";
$recentLogs = DB::table('push_notification_logs')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentLogs->count() > 0) {
    foreach ($recentLogs as $log) {
        $statusIcon = $log->status === 'sent' ? '✅' : '❌';
        echo "   $statusIcon {$log->created_at} - {$log->title}\n";
        echo "      Status: {$log->status}\n";
        if ($log->error_message) {
            echo "      Error: {$log->error_message}\n";
        }
        $tokenPreview = substr($log->fcm_token, 0, 30) . '...';
        echo "      Token: $tokenPreview\n";
        echo "\n";
    }
} else {
    echo "   No notification logs found for this user.\n\n";
}

echo "===========================================\n\n";
