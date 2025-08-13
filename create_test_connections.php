<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Creating Test Users with Connections\n";
echo "===================================\n\n";

try {
    // Find some existing users to create connections between
    $users = User::take(5)->get();

    if ($users->count() >= 2) {
        $user1 = $users[0];
        $user2 = $users[1];

        echo "Creating connection between:\n";
        echo "User 1: {$user1->name} (ID: {$user1->id})\n";
        echo "User 2: {$user2->name} (ID: {$user2->id})\n\n";

        // Check if connection already exists
        $existingConnection = UserRequest::where(function($query) use ($user1, $user2) {
                $query->where(['sender_id' => $user1->id, 'receiver_id' => $user2->id])
                      ->orWhere(['sender_id' => $user2->id, 'receiver_id' => $user1->id]);
            })->first();

        if (!$existingConnection) {
            // Create a connection request
            $connection = UserRequest::create([
                'sender_id' => $user1->id,
                'receiver_id' => $user2->id,
                'request_type' => 'right_swipe',
                'status' => 'accepted',
                'sender_status' => 'accepted',
                'receiver_status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Connection created successfully (ID: {$connection->id})\n";

            // Verify the connection counts
            $user1ConnectionCount = \App\Helpers\UserRequestsHelper::getConnectionCount($user1->id);
            $user2ConnectionCount = \App\Helpers\UserRequestsHelper::getConnectionCount($user2->id);

            echo "User 1 connection count: $user1ConnectionCount\n";
            echo "User 2 connection count: $user2ConnectionCount\n\n";

        } else {
            echo "⚠️ Connection already exists between these users\n";
            echo "Existing connection status: {$existingConnection->status}\n\n";
        }

        // Add more connections if we have more users
        if ($users->count() >= 3) {
            $user3 = $users[2];

            echo "Creating another connection:\n";
            echo "User 1: {$user1->name} (ID: {$user1->id})\n";
            echo "User 3: {$user3->name} (ID: {$user3->id})\n\n";

            $existingConnection2 = UserRequest::where(function($query) use ($user1, $user3) {
                    $query->where(['sender_id' => $user1->id, 'receiver_id' => $user3->id])
                          ->orWhere(['sender_id' => $user3->id, 'receiver_id' => $user1->id]);
                })->first();

            if (!$existingConnection2) {
                $connection2 = UserRequest::create([
                    'sender_id' => $user1->id,
                    'receiver_id' => $user3->id,
                    'request_type' => 'right_swipe',
                    'status' => 'accepted',
                    'sender_status' => 'accepted',
                    'receiver_status' => 'accepted',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                echo "✅ Second connection created successfully (ID: {$connection2->id})\n";

                // Verify updated connection counts
                $user1NewCount = \App\Helpers\UserRequestsHelper::getConnectionCount($user1->id);
                echo "User 1 updated connection count: $user1NewCount\n\n";

            } else {
                echo "⚠️ Connection already exists between User 1 and User 3\n\n";
            }
        }

        // Show final statistics
        echo "Final Statistics:\n";
        echo "================\n";
        $totalAcceptedConnections = UserRequest::where('status', 'accepted')
            ->where('sender_status', 'accepted')
            ->where('receiver_status', 'accepted')
            ->count();
        echo "Total accepted connections in database: $totalAcceptedConnections\n";

        echo "\nConnection counts for test users:\n";
        foreach ($users->take(3) as $user) {
            $connectionCount = \App\Helpers\UserRequestsHelper::getConnectionCount($user->id);
            echo "- {$user->name} (ID: {$user->id}): $connectionCount connections\n";
        }

    } else {
        echo "❌ Not enough users in database to create test connections\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest data creation completed!\n";
?>
