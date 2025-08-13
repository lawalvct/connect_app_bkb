<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "=== Admin Push Notification System Test ===\n\n";

// 1. Check if AdminFcmToken model works
echo "1. Testing AdminFcmToken model...\n";
try {
    $tokenCount = \App\Models\AdminFcmToken::count();
    echo "   ✓ AdminFcmToken model loaded successfully\n";
    echo "   ✓ Current admin FCM tokens: {$tokenCount}\n";
} catch (Exception $e) {
    echo "   ✗ Error with AdminFcmToken model: " . $e->getMessage() . "\n";
}

// 2. Check Admin model relationship
echo "\n2. Testing Admin model relationships...\n";
try {
    $admin = \App\Models\Admin::first();
    if ($admin) {
        echo "   ✓ Admin found: {$admin->name} ({$admin->email})\n";

        $tokens = $admin->fcmTokens;
        echo "   ✓ FCM tokens relationship works\n";
        echo "   ✓ Admin has {$tokens->count()} FCM tokens\n";

        $activeTokens = $admin->activeFcmTokens;
        echo "   ✓ Active FCM tokens: {$activeTokens->count()}\n";
    } else {
        echo "   ✗ No admin found in database\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error with Admin relationships: " . $e->getMessage() . "\n";
}

// 3. Check if routes are accessible
echo "\n3. Testing notification routes...\n";
$routes = [
    'admin.notifications.subscription.index',
    'admin.notifications.push.index'
];

foreach ($routes as $routeName) {
    try {
        $url = route($routeName);
        echo "   ✓ Route '{$routeName}' -> {$url}\n";
    } catch (Exception $e) {
        echo "   ✗ Route '{$routeName}' error: " . $e->getMessage() . "\n";
    }
}

// 4. Test NotificationController methods
echo "\n4. Testing NotificationController...\n";
try {
    $controller = new \App\Http\Controllers\Admin\NotificationController();
    echo "   ✓ NotificationController instantiated successfully\n";

    // Check if methods exist
    $methods = [
        'subscribeAdmin',
        'unsubscribeAdmin',
        'getAdminTokens',
        'updateAdminPreferences',
        'notifyAdmins',
        'testAdminNotification'
    ];

    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "   ✓ Method '{$method}' exists\n";
        } else {
            echo "   ✗ Method '{$method}' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ NotificationController error: " . $e->getMessage() . "\n";
}

// 5. Test Firebase configuration
echo "\n5. Testing Firebase configuration...\n";
$firebaseConfigs = [
    'services.firebase.api_key',
    'services.firebase.auth_domain',
    'services.firebase.project_id',
    'services.firebase.storage_bucket',
    'services.firebase.messaging_sender_id',
    'services.firebase.app_id',
    'services.firebase.vapid_key'
];

foreach ($firebaseConfigs as $config) {
    $value = config($config);
    if ($value) {
        echo "   ✓ {$config}: Set (" . strlen($value) . " chars)\n";
    } else {
        echo "   ✗ {$config}: Not set\n";
    }
}

// 6. Test default preferences
echo "\n6. Testing AdminFcmToken default preferences...\n";
try {
    $defaultPrefs = \App\Models\AdminFcmToken::getDefaultPreferences();
    echo "   ✓ Default preferences loaded\n";
    foreach ($defaultPrefs as $key => $value) {
        echo "   ✓ {$key}: " . ($value ? 'enabled' : 'disabled') . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error getting default preferences: " . $e->getMessage() . "\n";
}

// 7. Check if files exist
echo "\n7. Checking required files...\n";
$files = [
    'resources/views/admin/notifications/subscription.blade.php',
    'public/firebase-messaging.js',
    'public/firebase-messaging-sw.js'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✓ {$file} exists\n";
    } else {
        echo "   ✗ {$file} missing\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If all tests pass, the admin push notification system is ready!\n";
echo "\nNext steps:\n";
echo "1. Configure Firebase credentials in .env\n";
echo "2. Visit /admin/notifications/subscription to test\n";
echo "3. Subscribe to notifications\n";
echo "4. Send test notifications\n\n";
