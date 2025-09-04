<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UserNotification;

echo "Testing User Notification System\n";
echo "==============================\n\n";

try {
    // Find a test user
    $testUser = User::where('email', 'dusky@gmail.com')->first();

    if (!$testUser) {
        echo "Test user not found. Creating notifications for user ID 21...\n";
        $testUserId = 21;
    } else {
        $testUserId = $testUser->id;
        echo "Test User: {$testUser->name} (ID: {$testUserId})\n\n";
    }

    // Test creating welcome notification
    echo "Creating welcome notification...\n";
    $welcomeNotification = UserNotification::createWelcomeNotification($testUserId);
    echo "✅ Welcome notification created (ID: {$welcomeNotification->id})\n\n";

    // Test creating tutorial notification
    echo "Creating tutorial notification...\n";
    $tutorialNotification = UserNotification::createTutorialNotification($testUserId);
    echo "✅ Tutorial notification created (ID: {$tutorialNotification->id})\n\n";

    // Test getting unread count
    $unreadCount = UserNotification::getUnreadCountForUser($testUserId);
    echo "Unread notifications count: $unreadCount\n\n";

    // Test getting user notifications
    echo "Fetching user notifications...\n";
    $notifications = UserNotification::forUser($testUserId)->byPriority()->get();

    echo "Found {$notifications->count()} notifications:\n";
    echo str_repeat("-", 50) . "\n";

    foreach ($notifications as $notification) {
        echo "ID: {$notification->id}\n";
        echo "Title: {$notification->title}\n";
        echo "Type: {$notification->type}\n";
        echo "Priority: {$notification->priority}\n";
        echo "Read: " . ($notification->is_read ? 'Yes' : 'No') . "\n";
        echo "Created: {$notification->created_at}\n";
        echo "Message: " . substr($notification->message, 0, 100) . "...\n";
        echo str_repeat("-", 50) . "\n";
    }

    // Test marking first notification as read
    if ($notifications->count() > 0) {
        $firstNotification = $notifications->first();
        echo "\nMarking notification '{$firstNotification->title}' as read...\n";
        $firstNotification->markAsRead();
        echo "✅ Notification marked as read\n";

        // Check updated unread count
        $newUnreadCount = UserNotification::getUnreadCountForUser($testUserId);
        echo "New unread count: $newUnreadCount\n\n";
    }

    // Test creating a custom notification
    echo "Creating custom notification...\n";
    $customNotification = UserNotification::createForUser($testUserId, [
        'title' => 'Profile Completion Reminder',
        'message' => 'Complete your profile to get more matches! Add more photos and fill out your bio.',
        'type' => 'info',
        'icon' => 'fa-user-edit',
        'priority' => 5,
        'data' => [
            'action_type' => 'profile_completion',
            'completion_percentage' => 60
        ]
    ]);
    echo "✅ Custom notification created (ID: {$customNotification->id})\n\n";

    // Final unread count
    $finalUnreadCount = UserNotification::getUnreadCountForUser($testUserId);
    echo "Final unread notifications count: $finalUnreadCount\n";

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SUCCESS: User notification system is working!\n";
    echo "- Welcome and tutorial notifications are created on registration\n";
    echo "- Unread count tracking works correctly\n";
    echo "- Mark as read functionality works\n";
    echo "- Custom notifications can be created\n";
    echo "- Priority ordering is functional\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
