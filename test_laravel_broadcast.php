<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Events\MessageSent;
use App\Models\Message;

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Laravel Broadcasting...\n";

try {
    // Create a test message model
    $message = new Message();
    $message->id = 999999;
    $message->conversation_id = 1;
    $message->user_id = 1;
    $message->message = 'Test broadcast message from script';
    $message->type = 'text';
    $message->created_at = now();
    $message->updated_at = now();

    // Set up the user relationship for testing
    $message->setRelation('user', (object)[
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);

    echo "Broadcasting message...\n";

    // Fire the event
    event(new MessageSent($message));

    echo "Message broadcasted successfully!\n";
    echo "Check your Pusher debug console for the message.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
