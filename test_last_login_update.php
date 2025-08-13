<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing last_login_at Column Update ===\n\n";

try {
    // Find or create a test admin
    $admin = Admin::where('email', 'test@example.com')->first();

    if (!$admin) {
        echo "Creating test admin...\n";
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => Admin::ROLE_ADMIN,
            'status' => Admin::STATUS_ACTIVE,
            'permissions' => ['view_analytics', 'manage_users']
        ]);
        echo "✓ Test admin created with ID: {$admin->id}\n\n";
    } else {
        echo "✓ Using existing test admin with ID: {$admin->id}\n\n";
    }

    // Display initial state
    echo "Initial last_login_at: " . ($admin->last_login_at ? $admin->last_login_at->format('Y-m-d H:i:s') : 'NULL') . "\n\n";

    // Test the clearOtp method which should update last_login_at
    echo "Testing clearOtp() method...\n";
    $beforeUpdate = now();
    sleep(1); // Small delay to ensure timestamp difference

    $admin->clearOtp();
    $admin->refresh(); // Reload from database

    echo "✓ clearOtp() method executed\n";
    echo "Updated last_login_at: " . ($admin->last_login_at ? $admin->last_login_at->format('Y-m-d H:i:s') : 'NULL') . "\n";

    if ($admin->last_login_at && $admin->last_login_at->greaterThan($beforeUpdate)) {
        echo "✅ SUCCESS: last_login_at was updated correctly!\n";
    } else {
        echo "❌ FAILURE: last_login_at was not updated properly!\n";
    }

    echo "\n=== Test Details ===\n";
    echo "Admin ID: {$admin->id}\n";
    echo "Admin Name: {$admin->name}\n";
    echo "Admin Email: {$admin->email}\n";
    echo "Admin Status: {$admin->status}\n";
    echo "Admin Role: {$admin->role}\n";
    echo "Last Login: " . ($admin->last_login_at ? $admin->last_login_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "Failed Login Attempts: {$admin->failed_login_attempts}\n";
    echo "Is Locked: " . ($admin->isLocked() ? 'Yes' : 'No') . "\n";

    // Test the needsOtpVerification method
    echo "\nOTP Verification Status:\n";
    echo "Needs OTP Verification: " . ($admin->needsOtpVerification() ? 'Yes' : 'No') . "\n";

    echo "\n=== Test Completed ===\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
