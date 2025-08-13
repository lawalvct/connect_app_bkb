<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Connection Count Integration\n";
echo "==================================\n\n";

try {
    // Test the exact logic used in the controller
    $users = \App\Models\User::limit(3)->get();

    foreach ($users as $user) {
        echo "User: {$user->name} (ID: {$user->id})\n";

        // Test the connection count logic exactly as used in controller
        $connectionCount = \App\Helpers\UserRequestsHelper::getConnectionCount($user->id);
        echo "- Connection count: $connectionCount\n";

        // Test the same assignment as in controller
        $user->connections_count = $connectionCount;
        echo "- Assigned connections_count: {$user->connections_count}\n\n";
    }

    echo "Testing successful! ✅\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\nIntegration test completed!\n";
?>
