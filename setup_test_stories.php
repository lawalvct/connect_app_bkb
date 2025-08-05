<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;

echo "Creating Properly Expired Test Stories\n";
echo "=====================================\n\n";

$user = User::first();
echo "Using user: {$user->name} (ID: {$user->id})\n";
echo "Current time: " . Carbon::now() . "\n\n";

// Clear existing test stories
echo "Clearing existing stories...\n";
Story::truncate();

// Create expired stories with past dates
echo "Creating expired stories...\n";
for ($i = 1; $i <= 3; $i++) {
    $expiredAt = Carbon::now()->subHours(rand(1, 48)); // Expired 1-48 hours ago
    $story = Story::create([
        'user_id' => $user->id,
        'type' => 'text',
        'content' => "Expired story content {$i}",
        'caption' => "Expired story {$i}",
        'privacy' => 'all_connections',
        'allow_replies' => true,
        'views_count' => rand(1, 20),
        'expires_at' => $expiredAt,
    ]);
    echo "✅ Created expired story ID: {$story->id}\n";
    echo "   Expires at: {$story->expires_at}\n";
    echo "   Is expired: " . ($story->expires_at <= Carbon::now() ? 'YES' : 'NO') . "\n\n";
}

// Create active stories with future dates
echo "Creating active stories...\n";
for ($i = 1; $i <= 2; $i++) {
    $expiresAt = Carbon::now()->addHours(rand(1, 24)); // Expires in 1-24 hours
    $story = Story::create([
        'user_id' => $user->id,
        'type' => 'text',
        'content' => "Active story content {$i}",
        'caption' => "Active story {$i}",
        'privacy' => 'all_connections',
        'allow_replies' => true,
        'views_count' => rand(1, 20),
        'expires_at' => $expiresAt,
    ]);
    echo "✅ Created active story ID: {$story->id}\n";
    echo "   Expires at: {$story->expires_at}\n";
    echo "   Is expired: " . ($story->expires_at <= Carbon::now() ? 'YES' : 'NO') . "\n\n";
}

echo "Final verification:\n";
echo "- Total stories: " . Story::count() . "\n";
echo "- Active stories: " . Story::active()->count() . "\n";
echo "- Expired stories: " . Story::expired()->count() . "\n\n";

// Show expired stories details
$expiredStories = Story::expired()->get();
if ($expiredStories->count() > 0) {
    echo "Expired stories details:\n";
    foreach ($expiredStories as $story) {
        echo "- ID: {$story->id}, Caption: {$story->caption}, Expired: {$story->expires_at}\n";
    }
} else {
    echo "❌ No expired stories found!\n";
}

echo "\n✅ Test data setup completed!\n";
echo "Now go to /admin/stories and test the 'Cleanup Expired' button.\n";
