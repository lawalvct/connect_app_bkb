<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use Carbon\Carbon;

echo "Testing Story Cleanup Functionality\n";
echo "===================================\n\n";

// Check current stories
$totalStories = Story::count();
$activeStories = Story::active()->count();
$expiredStories = Story::expired()->count();

echo "Current Story Counts:\n";
echo "- Total Stories: {$totalStories}\n";
echo "- Active Stories: {$activeStories}\n";
echo "- Expired Stories: {$expiredStories}\n\n";

// Check if there are any expired stories
if ($expiredStories > 0) {
    echo "Found {$expiredStories} expired stories!\n";

    // Show some examples
    $expiredExamples = Story::expired()->take(3)->get(['id', 'type', 'caption', 'expires_at']);
    echo "Examples of expired stories:\n";
    foreach ($expiredExamples as $story) {
        echo "- ID: {$story->id}, Type: {$story->type}, Caption: " . ($story->caption ?: 'N/A') . ", Expired: {$story->expires_at}\n";
    }
} else {
    echo "No expired stories found.\n";
    echo "Creating a test expired story...\n";

    // Create a test expired story
    $testStory = Story::create([
        'user_id' => 1, // Assuming user ID 1 exists
        'type' => 'text',
        'content' => 'Test expired story',
        'caption' => 'Test cleanup functionality',
        'privacy' => 'all_connections',
        'allow_replies' => true,
        'views_count' => 0,
        'expires_at' => Carbon::now()->subHour(), // Expired 1 hour ago
    ]);

    echo "Created test story with ID: {$testStory->id}\n";
    echo "This story expires at: {$testStory->expires_at}\n";
    echo "Current time: " . Carbon::now() . "\n";
    echo "Story is expired: " . ($testStory->expires_at <= Carbon::now() ? 'YES' : 'NO') . "\n\n";

    // Re-check expired count
    $expiredStories = Story::expired()->count();
    echo "After creating test story, expired count: {$expiredStories}\n";
}

echo "\nCleanup functionality should work if:\n";
echo "1. The route '/admin/api/stories/cleanup-expired' exists\n";
echo "2. The controller method cleanupExpired() is working\n";
echo "3. The Story model has the expired() scope\n";
echo "4. The frontend JavaScript is properly calling the API\n\n";

echo "Test completed!\n";
