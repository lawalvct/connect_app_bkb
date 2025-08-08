<?php

// Final test to verify CallInitiated broadcasting is working

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Final CallInitiated Test ===\n\n";

try {
    // Check configuration
    $broadcastConnection = config('broadcasting.default');
    echo "Broadcasting driver: {$broadcastConnection}\n";

    if ($broadcastConnection === 'pusher') {
        echo "✅ Broadcasting correctly set to Pusher\n\n";

        // Test the actual event from CallController
        echo "Simulating CallController->initiate() broadcasting...\n";

        $call = \App\Models\Call::first();
        $conversation = \App\Models\Conversation::first();
        $user = \App\Models\User::first();

        if ($call && $conversation && $user) {
            // This is exactly what's in your CallController
            echo "Broadcasting: broadcast(new CallInitiated(\$call, \$conversation, \$user))->toOthers()\n";

            try {
                broadcast(new \App\Events\CallInitiated($call, $conversation, $user))->toOthers();
                echo "✅ SUCCESS: CallInitiated event broadcasted to Pusher!\n";
                echo "✅ Check your Pusher debug console now.\n";
                echo "✅ Channel: private-conversation.{$conversation->id}\n";
                echo "✅ Event: call.initiated\n\n";

                echo "Expected data in Pusher:\n";
                $event = new \App\Events\CallInitiated($call, $conversation, $user);
                echo json_encode($event->broadcastWith(), JSON_PRETTY_PRINT) . "\n";

            } catch (\Exception $e) {
                echo "❌ Failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "❌ No test data available\n";
        }
    } else {
        echo "❌ Broadcasting not set to pusher: {$broadcastConnection}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
