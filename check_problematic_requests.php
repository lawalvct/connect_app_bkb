<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking for problematic connection requests...\n\n";

try {
    // Check for requests with null sender_id
    $nullSenderIdCount = \App\Models\UserRequest::whereNull('sender_id')->count();
    echo "1. Requests with null sender_id: $nullSenderIdCount\n";

    // Check for requests where sender doesn't exist
    $missingSenderCount = \App\Models\UserRequest::whereDoesntHave('sender')->count();
    echo "2. Requests with missing sender: $missingSenderCount\n";

    // Check for requests with null receiver_id
    $nullReceiverIdCount = \App\Models\UserRequest::whereNull('receiver_id')->count();
    echo "3. Requests with null receiver_id: $nullReceiverIdCount\n";

    // Check for requests where receiver doesn't exist
    $missingReceiverCount = \App\Models\UserRequest::whereDoesntHave('receiver')->count();
    echo "4. Requests with missing receiver: $missingReceiverCount\n";

    // Show problematic requests
    if ($nullSenderIdCount > 0 || $missingSenderCount > 0) {
        echo "\n⚠️  Problematic requests found:\n";

        $problematicRequests = \App\Models\UserRequest::where(function($query) {
            $query->whereNull('sender_id')
                  ->orWhereDoesntHave('sender');
        })->get();

        foreach ($problematicRequests as $req) {
            echo "   - Request {$req->id}: sender_id={$req->sender_id}, receiver_id={$req->receiver_id}, status={$req->status}\n";
        }
    }

    echo "\n✅ Database check completed\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
