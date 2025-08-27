<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\AdminNotificationEvent;

echo "=== Testing AdminNotificationEvent ===\n";

try {
    // Test creating the event
    $data = [
        'title' => 'Test Notification',
        'message' => 'This is a test notification',
        'type' => 'test',
        'user_id' => 1,
        'created_at' => now()->toDateTimeString(),
    ];

    $event = new AdminNotificationEvent($data);
    echo "✅ Event created successfully\n";

    // Test broadcasting (this will actually send to Pusher if configured)
    // broadcast($event);
    // echo "✅ Event broadcasted successfully\n";

    echo "Event data: " . json_encode($event->data, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
