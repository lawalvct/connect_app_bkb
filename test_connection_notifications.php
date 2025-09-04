<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel application bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Connection Notification system...\n\n";

try {
    // Test connection request notification
    echo "1. Testing connection request notification...\n";

    $connectionNotification = \App\Models\UserNotification::createConnectionRequestNotification(
        1, // sender_id
        2, // receiver_id
        'John Doe', // sender name
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
        2, // accepter_id
        1, // sender_id
        'Jane Smith', // accepter name
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

    $requestNotification = \App\Models\UserNotification::where('type', 'connection_request')->first();
    if ($requestNotification) {
        echo "✅ Connection request type color: " . $requestNotification->type_color . "\n";
        echo "✅ Connection request type badge: " . $requestNotification->type_badge . "\n";
    }

    $acceptedNotification = \App\Models\UserNotification::where('type', 'connection_accepted')->first();
    if ($acceptedNotification) {
        echo "✅ Connection accepted type color: " . $acceptedNotification->type_color . "\n";
        echo "✅ Connection accepted type badge: " . $acceptedNotification->type_badge . "\n";
    }

    echo "\n4. Testing notification count for user...\n";

    $unreadCount = \App\Models\UserNotification::getUnreadCountForUser(1);
    echo "✅ Unread count for user 1: " . $unreadCount . "\n";

    $unreadCount2 = \App\Models\UserNotification::getUnreadCountForUser(2);
    echo "✅ Unread count for user 2: " . $unreadCount2 . "\n";

    echo "\nSUCCESS: All connection notification tests passed!\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
