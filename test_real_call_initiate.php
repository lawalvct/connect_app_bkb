<?php

require_once 'vendor/autoload.php';

// Load Laravel
require_once 'bootstrap/app.php';

echo "=== Testing Real Call Initiation with Direct Pusher ===\n\n";

try {
    // Get a test user (assuming ID 1 exists)
    $user = App\Models\User::find(1);
    if (!$user) {
        echo "❌ ERROR: Test user with ID 1 not found\n";
        exit(1);
    }

    echo "✅ Using test user: {$user->name} (ID: {$user->id})\n";

    // Get or create a test conversation
    $conversation = App\Models\Conversation::where('type', 'private')->first();
    if (!$conversation) {
        echo "❌ ERROR: No conversation found for testing\n";
        exit(1);
    }

    echo "✅ Using conversation ID: {$conversation->id}\n\n";

    // Check if there's already an active call
    $activeCall = App\Models\Call::where('conversation_id', $conversation->id)
        ->whereIn('status', ['initiated', 'ringing', 'connected'])
        ->first();

    if ($activeCall) {
        echo "⚠️  Active call found (ID: {$activeCall->id}), ending it first...\n";
        $activeCall->update([
            'status' => 'ended',
            'ended_at' => now(),
            'end_reason' => 'ended_for_test'
        ]);
        echo "✅ Previous call ended\n\n";
    }

    // Create the request data
    $requestData = [
        'conversation_id' => $conversation->id,
        'call_type' => 'audio'
    ];

    echo "📞 Initiating call with data:\n";
    echo "   Conversation ID: {$conversation->id}\n";
    echo "   Call Type: audio\n";
    echo "   Initiated By: {$user->id} ({$user->name})\n\n";

    // Simulate the CallController initiate method
    $callController = new App\Http\Controllers\API\V1\CallController();

    // Create a mock request
    $request = new Illuminate\Http\Request();
    $request->replace($requestData);

    // Set the authenticated user
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    echo "🚀 Calling CallController::initiate()...\n";
    $response = $callController->initiate($request);

    echo "📱 Response received:\n";
    $responseData = json_decode($response->getContent(), true);

    if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
        echo "✅ SUCCESS: Call initiated successfully!\n";
        echo "   Status Code: {$response->getStatusCode()}\n";
        echo "   Message: {$responseData['message']}\n";

        if (isset($responseData['data']['call'])) {
            $callData = $responseData['data']['call'];
            echo "   Call ID: {$callData['id']}\n";
            echo "   Channel Name: {$callData['agora_channel_name']}\n";
            echo "   Status: {$callData['status']}\n";
        }

        echo "\n📡 Check Pusher debug console for 'call.initiated' event on:\n";
        echo "   Channel: private-conversation.{$conversation->id}\n";
        echo "   Event: call.initiated\n";
        echo "   URL: https://dashboard.pusher.com/apps/1471502/console\n";

    } else {
        echo "❌ FAILED: Call initiation failed\n";
        echo "   Status Code: {$response->getStatusCode()}\n";
        echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Real Call Test Complete!\n";
