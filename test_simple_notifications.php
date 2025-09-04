<?php

// Simple test script to verify UserNotification system
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test basic functionality
echo "Testing UserNotification system...\n\n";

try {
    // Test 1: Check if we can create a notification
    $userId = 21; // Test user ID

    echo "1. Testing UserNotification creation...\n";
    $notification = App\Models\UserNotification::create([
        'title' => 'Test Notification',
        'message' => 'This is a test notification',
        'type' => 'info',
        'user_id' => $userId,
        'icon' => 'fa-test',
        'priority' => 5
    ]);

    echo "✅ Notification created with ID: {$notification->id}\n\n";

    // Test 2: Get unread count
    echo "2. Testing unread count...\n";
    $unreadCount = App\Models\UserNotification::getUnreadCountForUser($userId);
    echo "✅ Unread count: $unreadCount\n\n";

    // Test 3: Get notifications for user
    echo "3. Testing notification retrieval...\n";
    $notifications = App\Models\UserNotification::forUser($userId)->get();
    echo "✅ Found {$notifications->count()} notifications\n\n";

    // Test 4: Mark as read
    echo "4. Testing mark as read...\n";
    $notification->markAsRead();
    echo "✅ Notification marked as read\n\n";

    // Test 5: Create welcome notification
    echo "5. Testing welcome notification creation...\n";
    $welcome = App\Models\UserNotification::createWelcomeNotification($userId);
    echo "✅ Welcome notification created with ID: {$welcome->id}\n\n";

    echo "SUCCESS: All tests passed!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
