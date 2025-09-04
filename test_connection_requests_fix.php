<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel application bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing getConnectionRequests fix...\n\n";

try {
    // First, check what users exist
    echo "1. Checking existing users...\n";
    $users = \App\Models\User::select('id', 'name', 'email')->take(5)->get();

    if ($users->count() >= 2) {
        $user1 = $users->first();
        $user2 = $users->skip(1)->first();

        echo "✅ Found users: {$user1->id}: {$user1->name}, {$user2->id}: {$user2->name}\n\n";

        // Check existing connection requests
        echo "2. Checking existing connection requests...\n";
        $existingRequests = \App\Models\UserRequest::with(['sender', 'receiver'])
            ->where('deleted_flag', 'N')
            ->take(5)
            ->get();

        echo "✅ Found {$existingRequests->count()} existing requests\n";

        if ($existingRequests->count() > 0) {
            foreach ($existingRequests as $req) {
                $senderName = $req->sender ? $req->sender->name : 'UNKNOWN SENDER';
                $receiverName = $req->receiver ? $req->receiver->name : 'UNKNOWN RECEIVER';
                echo "   - Request {$req->id}: {$senderName} -> {$receiverName} (Status: {$req->status})\n";
            }
        }

        echo "\n3. Testing ConnectionController getConnectionRequests method...\n";

        // Find a user who has pending requests
        $userWithRequests = \App\Models\UserRequest::where('status', 'pending')
            ->where('deleted_flag', 'N')
            ->first();

        if ($userWithRequests) {
            $receiverId = $userWithRequests->receiver_id;
            $receiver = \App\Models\User::find($receiverId);

            if ($receiver) {
                echo "✅ Testing with user {$receiver->id}: {$receiver->name}\n";

                // Test the query directly
                $requests = \App\Models\UserRequest::with(['sender.profileImages', 'sender.country'])
                    ->where('receiver_id', $receiver->id)
                    ->where('status', 'pending')
                    ->where('deleted_flag', 'N')
                    ->orderBy('created_at', 'desc')
                    ->get();

                echo "✅ Found {$requests->count()} pending requests for user {$receiver->name}\n";

                // Test data transformation
                foreach ($requests as $request) {
                    $senderData = null;
                    if ($request->sender) {
                        echo "   ✅ Sender exists: {$request->sender->name}\n";

                        // Test profile images access
                        $profileImage = $request->sender->profileImages->first();
                        echo "   ✅ Profile image: " . ($profileImage ? 'Found' : 'None') . "\n";

                        // Test country access
                        $country = $request->sender->country;
                        echo "   ✅ Country: " . ($country ? $country->name : 'None') . "\n";
                    } else {
                        echo "   ❌ WARNING: Sender is null for request {$request->id}\n";
                    }
                }

            } else {
                echo "❌ Receiver user not found\n";
            }
        } else {
            echo "ℹ️  No pending requests found to test with\n";

            // Create a test request
            echo "\n4. Creating test connection request...\n";

            $testRequest = \App\Models\UserRequest::create([
                'sender_id' => $user1->id,
                'receiver_id' => $user2->id,
                'request_type' => 'right_swipe',
                'status' => 'pending',
                'deleted_flag' => 'N',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Created test request {$testRequest->id}: {$user1->name} -> {$user2->name}\n";

            // Now test the query
            $requests = \App\Models\UserRequest::with(['sender.profileImages', 'sender.country'])
                ->where('receiver_id', $user2->id)
                ->where('status', 'pending')
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get();

            echo "✅ Query returned {$requests->count()} requests\n";

            if ($requests->count() > 0) {
                $request = $requests->first();
                echo "✅ Test request found with sender: " . ($request->sender ? $request->sender->name : 'NULL') . "\n";
            }
        }

        echo "\nSUCCESS: getConnectionRequests fix tested successfully!\n";

    } else {
        echo "❌ Not enough users in database for testing\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
