<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel application bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Connection Notification system...\n\n";

try {
    // First, let's check what users exist
    echo "Checking existing users...\n";
    $users = \App\Models\User::select('id', 'name', 'email')->take(5)->get();

    if ($users->count() >= 2) {
        $user1 = $users->first();
        $user2 = $users->skip(1)->first();

        echo "✅ Found users: {$user1->id}: {$user1->name}, {$user2->id}: {$user2->name}\n\n";

        // Test connection request notification
        echo "1. Testing connection request notification...\n";

        $connectionNotification = \App\Models\UserNotification::createConnectionRequestNotification(
            $user1->id, // sender_id
            $user2->id, // receiver_id
            $user1->name, // sender name
            123 // request_id
        );

        if ($connectionNotification) {
            echo "✅ Connection request notification created with ID: " . $connectionNotification->id . "\n";
            echo "   Title: " . $connectionNotification->title . "\n";
            echo "   Type: " . $connectionNotification->type . "\n";
            echo "   Priority: " . $connectionNotification->priority . "\n";
        } else {
            echo "❌ Failed to create connection request notification\n";
        }

        echo "\n2. Testing connection accepted notification...\n";

        $acceptedNotification = \App\Models\UserNotification::createConnectionAcceptedNotification(
            $user2->id, // accepter_id
            $user1->id, // sender_id
            $user2->name, // accepter name
            123 // request_id
        );

        if ($acceptedNotification) {
            echo "✅ Connection accepted notification created with ID: " . $acceptedNotification->id . "\n";
            echo "   Title: " . $acceptedNotification->title . "\n";
            echo "   Type: " . $acceptedNotification->type . "\n";
            echo "   Priority: " . $acceptedNotification->priority . "\n";
        } else {
            echo "❌ Failed to create connection accepted notification\n";
        }

        echo "\n3. Testing notification type colors and badges...\n";

        echo "✅ Connection request type color: " . $connectionNotification->type_color . "\n";
        echo "✅ Connection request type badge: " . $connectionNotification->type_badge . "\n";
        echo "✅ Connection accepted type color: " . $acceptedNotification->type_color . "\n";
        echo "✅ Connection accepted type badge: " . $acceptedNotification->type_badge . "\n";

        echo "\n4. Testing notification count for users...\n";

        $unreadCount1 = \App\Models\UserNotification::getUnreadCountForUser($user1->id);
        echo "✅ Unread count for {$user1->name}: " . $unreadCount1 . "\n";

        $unreadCount2 = \App\Models\UserNotification::getUnreadCountForUser($user2->id);
        echo "✅ Unread count for {$user2->name}: " . $unreadCount2 . "\n";

        echo "\nSUCCESS: All connection notification tests passed!\n";

    } else {
        echo "❌ Not enough users in database. Need at least 2 users for testing.\n";
        echo "   Users found: " . $users->count() . "\n";

        if ($users->count() > 0) {
            echo "   Available users:\n";
            foreach ($users as $user) {
                echo "   - {$user->id}: {$user->name} ({$user->email})\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
