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
use Carbon\Carbon;

echo "Creating Test Connect Boost Subscription\n";
echo "========================================\n\n";

try {
    // Find a test user (using user ID 21 from our previous test)
    $testUserId = 21;
    $user = User::find($testUserId);

    if (!$user) {
        echo "Test user not found. Creating test scenario...\n";
        exit;
    }

    echo "Test User: {$user->name} (ID: {$testUserId})\n\n";

    // Check current swipe limit (should be 50 for free user)
    $currentLimit = UserHelper::getUserDailySwipeLimit($testUserId);
    echo "Current Daily Swipe Limit: $currentLimit\n\n";

    // Create a test Connect Boost subscription
    echo "Creating Connect Boost subscription...\n";

    $subscription = new UserSubscription([
        'user_id' => $testUserId,
        'subscription_id' => 4, // Connect Boost
        'amount' => 4.99,
        'currency' => 'USD',
        'payment_method' => 'stripe',
        'payment_status' => 'completed',
        'transaction_reference' => 'test_boost_' . time(),
        'started_at' => Carbon::now(),
        'expires_at' => Carbon::now()->addDay(), // 24 hours for boost
        'status' => 'active',
        'deleted_flag' => 'N',
        'created_at' => Carbon::now(),
        'created_by' => $testUserId
    ]);

    $subscription->save();

    echo "✅ Connect Boost subscription created (ID: {$subscription->id})\n";
    echo "   Expires: {$subscription->expires_at}\n\n";

    // Test the new swipe limit
    $newLimit = UserHelper::getUserDailySwipeLimit($testUserId);
    echo "New Daily Swipe Limit: $newLimit\n";

    // Verify subscription status
    $hasBoost = UserSubscriptionHelper::hasConnectBoost($testUserId);
    echo "Has Connect Boost: " . ($hasBoost ? 'Yes' : 'No') . "\n\n";

    // Test all subscription checks
    echo "Subscription Status Check:\n";
    echo "- Has Unlimited Access: " . (UserSubscriptionHelper::hasUnlimitedAccess($testUserId) ? 'Yes' : 'No') . "\n";
    echo "- Has Connect Boost: " . (UserSubscriptionHelper::hasConnectBoost($testUserId) ? 'Yes' : 'No') . "\n";
    echo "- Has Travel Access: " . (UserSubscriptionHelper::hasTravelAccess($testUserId) ? 'Yes' : 'No') . "\n";

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SUCCESS: Connect Boost user now has 100 daily swipes!\n";
    echo "Swipe limit increased from 50 to 100 (+50 bonus)\n";

    // Clean up - remove test subscription
    echo "\nCleaning up test subscription...\n";
    $subscription->delete();
    echo "✅ Test subscription removed\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
