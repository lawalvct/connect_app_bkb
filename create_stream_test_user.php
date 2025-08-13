<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Creating Test User for Stream Testing\n";
echo "====================================\n\n";

try {
    // Check if test user already exists
    $existingUser = User::where('email', 'stream.tester@connectapp.test')->first();

    if ($existingUser) {
        echo "Test user already exists:\n";
        echo "ID: {$existingUser->id}\n";
        echo "Name: {$existingUser->name}\n";
        echo "Email: {$existingUser->email}\n";
        echo "Status: {$existingUser->status}\n\n";
        exit(0);
    }

    // Create test user
    $testUser = User::create([
        'name' => 'Stream Tester',
        'email' => 'stream.tester@connectapp.test',
        'email_verified_at' => now(),
        'password' => Hash::make('password123'),
        'status' => 'active',
        'phone' => '+1234567890',
        'date_of_birth' => '1990-01-01',
        'gender' => 'other',
        'interests' => json_encode(['streaming', 'content creation']),
        'location' => 'Test City',
        'bio' => 'Test user for stream functionality testing',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "✅ Test user created successfully!\n";
    echo "ID: {$testUser->id}\n";
    echo "Name: {$testUser->name}\n";
    echo "Email: {$testUser->email}\n\n";
    echo "You can now use this user to create test streams.\n";

} catch (Exception $e) {
    echo "❌ Error creating test user: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
