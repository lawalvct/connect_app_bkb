<?php

require_once 'vendor/autoload.php';

use App\Helpers\UserRequestsHelper;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Connection Count Feature\n";
echo "================================\n\n";

try {
    // Test the UserRequestsHelper::getConnectionCount method
    $testUserId = 1; // Test with user ID 1

    echo "Testing UserRequestsHelper::getConnectionCount for user ID: $testUserId\n";
    $connectionCount = UserRequestsHelper::getConnectionCount($testUserId);
    echo "Connection count: $connectionCount\n\n";

    // Test getting user data with connection count
    echo "Testing user data with connection count:\n";
    $user = User::find($testUserId);
    if ($user) {
        echo "User: {$user->name}\n";
        echo "Email: {$user->email}\n";

        // Add connection count using the same logic as the controller
        $user->connections_count = UserRequestsHelper::getConnectionCount($user->id);
        echo "Connections count: {$user->connections_count}\n\n";
    } else {
        echo "User not found\n\n";
    }

    // Show some statistics about connections in the database
    echo "Database Statistics:\n";
    echo "===================\n";

    $totalUsers = User::count();
    echo "Total users: $totalUsers\n";

    $totalRequests = UserRequest::count();
    echo "Total user requests: $totalRequests\n";

    $acceptedConnections = UserRequest::where('status', 'accepted')
        ->where('sender_status', 'accepted')
        ->where('receiver_status', 'accepted')
        ->count();
    echo "Accepted connections: $acceptedConnections\n";

    $pendingRequests = UserRequest::where('status', 'pending')->count();
    echo "Pending requests: $pendingRequests\n";

    // Show users with most connections
    echo "\nUsers with connections:\n";
    $usersWithConnections = User::limit(5)->get();
    foreach ($usersWithConnections as $user) {
        $connectionCount = UserRequestsHelper::getConnectionCount($user->id);
        echo "- {$user->name} (ID: {$user->id}): $connectionCount connections\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed successfully!\n";
?>
