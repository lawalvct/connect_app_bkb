<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Creating test FCM tokens...\n";

try {
    // Get the first user (admin)
    $user = \App\Models\User::first();
    if (!$user) {
        echo "No users found. Please create a user first.\n";
        exit;
    }

    // Create a test FCM token
    $token = \App\Models\UserFcmToken::create([
        'user_id' => $user->id,
        'fcm_token' => 'test_fcm_token_' . uniqid(),
        'platform' => 'web',
        'app_version' => '1.0.0',
        'is_active' => true,
        'last_used_at' => now()
    ]);

    echo "Test FCM token created for user: " . $user->name . "\n";
    echo "Token ID: " . $token->id . "\n";
    echo "FCM Token: " . $token->fcm_token . "\n";
    echo "Total FCM tokens for user: " . $user->fcmTokens()->count() . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
