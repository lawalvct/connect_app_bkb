<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ExportReadyMail;
use App\Models\User;

echo "Testing Email Export Functionality\n";
echo "=================================\n\n";

// Test mail configuration
echo "1. Testing Mail Configuration:\n";
$config = config('mail');
echo "Mail Driver: " . $config['default'] . "\n";
echo "Mail Host: " . $config['mailers']['smtp']['host'] . "\n";
echo "Mail Port: " . $config['mailers']['smtp']['port'] . "\n";
echo "Mail Username: " . $config['mailers']['smtp']['username'] . "\n";
echo "Mail From: " . $config['from']['address'] . "\n\n";

// Get an admin user for testing
echo "2. Finding Admin User:\n";
$adminUser = User::where('email', 'lawalthb@gmail.com')->first();
if (!$adminUser) {
    echo "❌ Admin user not found. Creating test user...\n";
    $adminUser = new User();
    $adminUser->name = 'Test Admin';
    $adminUser->email = 'lawalthb@gmail.com';
    $adminUser->password = bcrypt('password');
    $adminUser->save();
}
echo "✅ Admin user found: " . $adminUser->email . "\n\n";

// Test email sending
echo "3. Testing Email Send:\n";
try {
    $testFilename = 'test_export_' . date('Y-m-d_H-i-s') . '.csv';

    echo "Attempting to send test email to: " . $adminUser->email . "\n";

    Mail::to($adminUser->email)->send(new ExportReadyMail($testFilename, 'csv'));

    echo "✅ Email sent successfully!\n";
    echo "Check your email inbox for the export notification.\n\n";

} catch (Exception $e) {
    echo "❌ Email send failed: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getTraceAsString() . "\n\n";
}

// Check if there are any failed jobs in the queue
echo "4. Checking Failed Jobs:\n";
try {
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    echo "Failed jobs in queue: " . $failedJobs . "\n";

    if ($failedJobs > 0) {
        echo "Recent failed jobs:\n";
        $recent = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get(['payload', 'exception', 'failed_at']);

        foreach ($recent as $job) {
            echo "- Failed at: " . $job->failed_at . "\n";
            echo "  Exception: " . substr($job->exception, 0, 200) . "...\n";
        }
    }
} catch (Exception $e) {
    echo "Could not check failed jobs: " . $e->getMessage() . "\n";
}

echo "\n5. Mail Log Check:\n";
try {
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        if (strpos($logContent, 'ExportReadyMail') !== false) {
            echo "✅ Found ExportReadyMail entries in log\n";
        } else {
            echo "❓ No ExportReadyMail entries found in recent logs\n";
        }
    } else {
        echo "❓ Log file not found\n";
    }
} catch (Exception $e) {
    echo "Could not check logs: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
