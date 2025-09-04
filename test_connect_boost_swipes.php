<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Helpers\UserHelper;
use App\Helpers\UserSubscriptionHelper;
use App\Models\User;
use App\Models\UserSubscription;

echo "Testing Connect Boost Swipe Limit Enhancement\n";
echo "============================================\n\n";

try {
    // Test with a few user IDs to see their subscription status and swipe limits
    $testUserIds = [21, 203, 322, 698]; // Using some user IDs from the subscription expiration output

    foreach ($testUserIds as $userId) {
        echo "User ID: $userId\n";
        echo str_repeat("-", 30) . "\n";

        // Get user info
        $user = User::find($userId);
        if (!$user) {
            echo "User not found\n\n";
            continue;
        }

        echo "User Name: " . $user->name . "\n";

        // Get active subscriptions
        $activeSubscriptions = UserSubscriptionHelper::getByUserId($userId);
        echo "Active Subscription IDs: " . implode(', ', $activeSubscriptions) . "\n";

        // Check subscription types
        $hasUnlimited = UserSubscriptionHelper::hasUnlimitedAccess($userId);
        $hasConnectBoost = UserSubscriptionHelper::hasConnectBoost($userId);
        $hasTravelAccess = UserSubscriptionHelper::hasTravelAccess($userId);

        echo "Has Unlimited Access: " . ($hasUnlimited ? 'Yes' : 'No') . "\n";
        echo "Has Connect Boost: " . ($hasConnectBoost ? 'Yes' : 'No') . "\n";
        echo "Has Travel Access: " . ($hasTravelAccess ? 'Yes' : 'No') . "\n";

        // Get daily swipe limit
        $swipeLimit = UserHelper::getUserDailySwipeLimit($userId);
        echo "Daily Swipe Limit: $swipeLimit\n";

        // Show calculation logic
        if ($hasUnlimited) {
            echo "Limit Reason: Unlimited subscription (999999 swipes)\n";
        } elseif ($hasConnectBoost) {
            echo "Limit Reason: Base 50 + Connect Boost 50 = 100 swipes\n";
        } else {
            echo "Limit Reason: Free user base limit (50 swipes)\n";
        }

        echo "\n";
    }

    // Test with subscription data
    echo "Subscription Plan Details from Database:\n";
    echo str_repeat("=", 50) . "\n";

    $subscriptions = \App\Models\Subscribe::all();
    foreach ($subscriptions as $sub) {
        echo "ID: {$sub->id} | Name: {$sub->name} | Features: " . implode(', ', $sub->features ?? []) . "\n";
    }

    echo "\n";
    echo "Connect Boost Logic Test:\n";
    echo str_repeat("=", 30) . "\n";

    // Create a test scenario
    echo "Test Scenario:\n";
    echo "- Free user (no subscription): 50 swipes\n";
    echo "- Connect Boost user (ID 4): 50 + 50 = 100 swipes\n";
    echo "- Connect Unlimited user (ID 2): 999999 swipes\n";
    echo "- Connect Premium user (ID 3): 999999 swipes\n";
    echo "- Connect Travel user (ID 1): 50 swipes (no boost)\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed.\n";
echo "Connect Boost users should now get +50 additional swipes (total 100).\n";
echo "This applies to sendRequest actions in the ConnectionController.\n";
?>
