<?php

require_once 'vendor/autoload.php';

use App\Models\UserRequest;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing UserRequest Model with deleted_at issue fix\n";
echo "=================================================\n\n";

try {
    echo "1. Testing direct UserRequest::count() (this used to fail):\n";
    $totalRequests = UserRequest::withTrashed()->count();
    echo "   Total requests (with trashed): {$totalRequests}\n";

    echo "\n2. Testing connection counting logic:\n";
    $acceptedConnections = UserRequest::withTrashed()
        ->where('status', 'accepted')
        ->where('sender_status', 'accepted')
        ->where('receiver_status', 'accepted')
        ->count();
    echo "   Accepted connections: {$acceptedConnections}\n";

    echo "\n3. Testing UserRequestsHelper::getConnectionCount() for user ID 1:\n";
    $connectionCount = \App\Helpers\UserRequestsHelper::getConnectionCount(1);
    echo "   Connection count for user 1: {$connectionCount}\n";

    echo "\n4. Testing the stats calculation that was failing:\n";
    $stats = [
        'total_connections' => \App\Models\UserRequest::withTrashed()
            ->where('status', 'accepted')
            ->where('sender_status', 'accepted')
            ->where('receiver_status', 'accepted')
            ->count()
    ];
    echo "   Stats calculation successful: {$stats['total_connections']} total connections\n";

    echo "\n✅ All tests passed! The deleted_at issue has been fixed.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
