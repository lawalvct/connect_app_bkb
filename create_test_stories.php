<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;

echo "Creating Test Data for Story Cleanup\n";
echo "====================================\n\n";

// Check if we have users
$userCount = User::count();
echo "Available users: {$userCount}\n";

if ($userCount === 0) {
    echo "❌ No users found. Cannot create test stories.\n";
    exit(1);
}

// Get the first user
$user = User::first();
echo "Using user: {$user->name} (ID: {$user->id})\n\n";

// Create some test stories - mix of active and expired
echo "Creating test stories...\n";

// Create 2 expired stories
for ($i = 1; $i <= 2; $i++) {
    $story = Story::create([
        'user_id' => $user->id,
        'type' => 'text',
        'content' => "Test expired story content {$i}",
        'caption' => "Test expired story {$i}",
        'privacy' => 'all_connections',
        'allow_replies' => true,
        'views_count' => rand(0, 10),
        'expires_at' => Carbon::now()->subHours(rand(1, 24)), // Expired 1-24 hours ago
    ]);
    echo "✅ Created expired story ID: {$story->id} (expires: {$story->expires_at})\n";
}

// Create 2 active stories
for ($i = 1; $i <= 2; $i++) {
    $story = Story::create([
        'user_id' => $user->id,
        'type' => 'text',
        'content' => "Test active story content {$i}",
        'caption' => "Test active story {$i}",
        'privacy' => 'all_connections',
        'allow_replies' => true,
        'views_count' => rand(0, 10),
        'expires_at' => Carbon::now()->addHours(rand(1, 24)), // Expires in 1-24 hours
    ]);
    echo "✅ Created active story ID: {$story->id} (expires: {$story->expires_at})\n";
}

echo "\nFinal counts:\n";
echo "- Total stories: " . Story::count() . "\n";
echo "- Active stories: " . Story::active()->count() . "\n";
echo "- Expired stories: " . Story::expired()->count() . "\n";

echo "\n✅ Test data created successfully!\n";
echo "Now you can test the cleanup functionality in the admin panel.\n";
echo "Go to: /admin/stories and click 'Cleanup Expired'\n";
