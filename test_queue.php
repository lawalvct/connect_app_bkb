<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

echo "Testing Queue System\n";
echo "==================\n\n";

// Check queue configuration
echo "1. Queue Configuration:\n";
echo "Queue Driver: " . config('queue.default') . "\n";
echo "Queue Connection: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n\n";

// Check if jobs table exists
echo "2. Checking Jobs Table:\n";
try {
    $jobsCount = DB::table('jobs')->count();
    echo "✅ Jobs table exists with {$jobsCount} pending jobs\n";
} catch (Exception $e) {
    echo "❌ Jobs table issue: " . $e->getMessage() . "\n";
}

// Check failed jobs
try {
    $failedCount = DB::table('failed_jobs')->count();
    echo "Failed jobs: {$failedCount}\n";

    if ($failedCount > 0) {
        echo "Recent failed job details:\n";
        $failed = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->first();
        if ($failed) {
            $payload = json_decode($failed->payload, true);
            echo "Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
            echo "Failed at: " . $failed->failed_at . "\n";
            echo "Exception: " . substr($failed->exception, 0, 300) . "...\n";
        }
    }
} catch (Exception $e) {
    echo "Could not check failed jobs: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Simple Queue Job:\n";
try {
    // Dispatch a simple test job
    Queue::push(function() {
        \Illuminate\Support\Facades\Log::info('Test queue job executed successfully');
    });
    echo "✅ Test job queued successfully\n";
} catch (Exception $e) {
    echo "❌ Queue test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Queue Test Complete ===\n";
