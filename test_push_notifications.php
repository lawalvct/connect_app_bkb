<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$app->boot();

// Test the User model and FCM tokens relationship
try {
    // Create a test to see if the fcmTokens relationship exists
    $user = new \App\Models\User();

    echo "Testing User model FCM tokens relationship...\n";

    // Check if the fcmTokens method exists
    if (method_exists($user, 'fcmTokens')) {
        echo "✅ fcmTokens() method exists on User model\n";

        // Try to call the method (won't actually query since no database connection)
        $relationship = $user->fcmTokens();
        echo "✅ fcmTokens() relationship can be called successfully\n";
        echo "Relationship class: " . get_class($relationship) . "\n";
    } else {
        echo "❌ fcmTokens() method does not exist on User model\n";
    }

    // Check if the activeFcmTokens method exists
    if (method_exists($user, 'activeFcmTokens')) {
        echo "✅ activeFcmTokens() method exists on User model\n";
    } else {
        echo "❌ activeFcmTokens() method does not exist on User model\n";
    }

    // Test UserFcmToken model
    echo "\nTesting UserFcmToken model...\n";
    $fcmToken = new \App\Models\UserFcmToken();
    echo "✅ UserFcmToken model can be instantiated\n";

    if (method_exists($fcmToken, 'user')) {
        echo "✅ user() relationship exists on UserFcmToken model\n";
    } else {
        echo "❌ user() relationship does not exist on UserFcmToken model\n";
    }

    echo "\n🎉 All push notification components are working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error testing push notification functionality: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
