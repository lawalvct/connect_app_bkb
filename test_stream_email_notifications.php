<?php

/**
 * Test script for Live Stream Email Notifications
 *
 * This script verifies the email notification system is working correctly
 * Run with: php test_stream_email_notifications.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Stream;
use App\Models\User;
use App\Jobs\SendLiveStreamNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Live Stream Email Notification Test ===\n\n";

// Step 1: Count eligible users
echo "Step 1: Counting eligible users...\n";
$eligibleUsers = User::where('is_active', true)
    ->where('deleted_flag', 'N')
    ->where('is_banned', false)
    ->whereNotNull('email')
    ->whereNotNull('email_verified_at')
    ->where('notification_email', true)
    ->count();

echo "✓ Found {$eligibleUsers} eligible users for notifications\n\n";

// Step 2: Check queue configuration
echo "Step 2: Checking queue configuration...\n";
$queueDriver = config('queue.default');
echo "✓ Queue driver: {$queueDriver}\n";

$pendingJobs = DB::table('jobs')->count();
echo "✓ Pending jobs in queue: {$pendingJobs}\n\n";

// Step 3: Find or create test stream
echo "Step 3: Checking for test stream...\n";
$testStream = Stream::where('status', 'live')
    ->orWhere('status', 'upcoming')
    ->orderBy('created_at', 'desc')
    ->first();

if ($testStream) {
    echo "✓ Found stream: '{$testStream->title}' (ID: {$testStream->id})\n";
    echo "  Status: {$testStream->status}\n";
    echo "  Created: {$testStream->created_at}\n\n";
} else {
    echo "⚠ No active or upcoming streams found\n";
    echo "  Create a stream via admin panel to test notifications\n\n";
}

// Step 4: Check if job class exists
echo "Step 4: Verifying job class...\n";
if (class_exists('App\Jobs\SendLiveStreamNotifications')) {
    echo "✓ SendLiveStreamNotifications job class found\n\n";
} else {
    echo "✗ SendLiveStreamNotifications job class NOT found\n\n";
}

// Step 5: Check if mailable class exists
echo "Step 5: Verifying mailable class...\n";
if (class_exists('App\Mail\NewLiveStreamNotification')) {
    echo "✓ NewLiveStreamNotification mailable class found\n\n";
} else {
    echo "✗ NewLiveStreamNotification mailable class NOT found\n\n";
}

// Step 6: Check email template
echo "Step 6: Checking email template...\n";
$templatePath = resource_path('views/emails/new-live-stream.blade.php');
if (file_exists($templatePath)) {
    echo "✓ Email template found at: {$templatePath}\n\n";
} else {
    echo "✗ Email template NOT found\n\n";
}

// Step 7: Test job dispatch (optional - uncomment to test)
echo "Step 7: Test job dispatch\n";
if ($testStream && $eligibleUsers > 0) {
    echo "To dispatch a test notification job, uncomment the code in this section.\n";
    echo "This will queue emails to {$eligibleUsers} users.\n\n";

    // UNCOMMENT BELOW TO ACTUALLY DISPATCH THE JOB
    /*
    echo "Dispatching job...\n";
    SendLiveStreamNotifications::dispatch($testStream);
    echo "✓ Job dispatched successfully\n";
    echo "  Run 'php artisan queue:work' to process the job\n\n";

    $newPendingJobs = DB::table('jobs')->count();
    echo "✓ Jobs in queue now: {$newPendingJobs}\n\n";
    */
} else {
    echo "⚠ Cannot dispatch job - no stream or no eligible users\n\n";
}

// Step 8: Check recent job logs
echo "Step 8: Checking recent logs...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $streamNotificationLogs = substr_count($logContent, 'live stream email notifications');
    echo "✓ Log file exists: {$logFile}\n";
    echo "  Found {$streamNotificationLogs} stream notification log entries\n\n";
} else {
    echo "⚠ Log file not found\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Eligible users: {$eligibleUsers}\n";
echo "Queue driver: {$queueDriver}\n";
echo "Pending jobs: {$pendingJobs}\n";
echo "Test stream: " . ($testStream ? "Found (ID: {$testStream->id})" : "Not found") . "\n";
echo "\n";

echo "=== Next Steps ===\n";
echo "1. Ensure queue worker is running:\n";
echo "   php artisan queue:work --tries=3 --timeout=300\n\n";
echo "2. Create a new stream via admin panel\n";
echo "3. Check jobs table:\n";
echo "   SELECT * FROM jobs ORDER BY id DESC LIMIT 5;\n\n";
echo "4. Monitor logs:\n";
echo "   tail -f storage/logs/laravel.log\n\n";
echo "5. Check email delivery (Mailtrap/production mail server)\n\n";

echo "Test completed!\n";
