<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Call Database Debug ===\n\n";

try {
    // Check total calls
    $totalCalls = \App\Models\Call::count();
    echo "Total calls in database: {$totalCalls}\n\n";

    if ($totalCalls > 0) {
        echo "Existing calls:\n";
        $calls = \App\Models\Call::orderBy('id')->get(['id', 'status', 'call_type', 'created_at']);

        foreach ($calls as $call) {
            echo "- Call ID: {$call->id}, Status: {$call->status}, Type: {$call->call_type}, Created: {$call->created_at}\n";
        }

        echo "\n";

        // Test finding call ID 3 specifically
        echo "Testing Call ID 3:\n";
        $call3 = \App\Models\Call::find(3);
        if ($call3) {
            echo "✅ Call ID 3 exists: Status = {$call3->status}, Type = {$call3->call_type}\n";
        } else {
            echo "❌ Call ID 3 does NOT exist in database\n";
        }
    } else {
        echo "No calls found in database.\n";
        echo "You may need to create test calls first.\n";
    }

    echo "\n=== Suggestions ===\n";
    if ($totalCalls === 0) {
        echo "1. Create a test call first using the /calls/initiate endpoint\n";
        echo "2. Or test with a call ID that actually exists\n";
    } else {
        $firstCall = \App\Models\Call::first();
        if ($firstCall) {
            echo "Try testing with Call ID: {$firstCall->id} (this one exists)\n";
            echo "Test URL: POST /api/v1/calls/{$firstCall->id}/end\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 40) . "\n";
