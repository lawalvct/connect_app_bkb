<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;

echo "Creating Test Stories for Cleanup Testing\n";
echo "=========================================\n\n";

$user = User::first();
echo "Using user: {$user->name} (ID: {$user->id})\n";
echo "Current time: " . Carbon::now() . "\n\n";

// Clear existing stories safely
echo "Clearing existing stories...\n";
Story::query()->delete();

// Create expired stories with past dates
echo "Creating expired stories...\n";
for ($i = 1; $i <= 3; $i++) {
    $hoursAgo = rand(1, 48);
    $expiredAt = Carbon::now()->subHours($hoursAgo);

    $story = new Story();
    $story->user_id = $user->id;
    $story->type = 'text';
    $story->content = "This is expired story content {$i}";
    $story->caption = "Expired Test Story {$i}";
    $story->privacy = 'all_connections';
    $story->allow_replies = true;
    $story->views_count = rand(1, 20);
    $story->expires_at = $expiredAt;
    $story->save();

    echo "✅ Created expired story ID: {$story->id}\n";
    echo "   Caption: {$story->caption}\n";
    echo "   Expires at: {$story->expires_at}\n";
    echo "   Hours ago: {$hoursAgo}\n";
    echo "   Is expired: " . ($story->expires_at->isPast() ? 'YES' : 'NO') . "\n\n";
}

// Create active stories with future dates
echo "Creating active stories...\n";
for ($i = 1; $i <= 2; $i++) {
    $hoursInFuture = rand(1, 24);
    $expiresAt = Carbon::now()->addHours($hoursInFuture);

    $story = new Story();
    $story->user_id = $user->id;
    $story->type = 'image';
    $story->content = "This is active story content {$i}";
    $story->caption = "Active Test Story {$i}";
    $story->privacy = 'all_connections';
    $story->allow_replies = true;
    $story->views_count = rand(1, 20);
    $story->expires_at = $expiresAt;
    $story->save();

    echo "✅ Created active story ID: {$story->id}\n";
    echo "   Caption: {$story->caption}\n";
    echo "   Expires at: {$story->expires_at}\n";
    echo "   Hours in future: {$hoursInFuture}\n";
    echo "   Is expired: " . ($story->expires_at->isPast() ? 'YES' : 'NO') . "\n\n";
}

echo "=== FINAL VERIFICATION ===\n";
$totalStories = Story::count();
$activeStories = Story::active()->count();
$expiredStories = Story::expired()->count();

echo "Total stories: {$totalStories}\n";
echo "Active stories: {$activeStories}\n";
echo "Expired stories: {$expiredStories}\n\n";

if ($expiredStories > 0) {
    echo "✅ SUCCESS! Found {$expiredStories} expired stories ready for cleanup.\n";
    echo "\nExpired stories list:\n";
    $expired = Story::expired()->get(['id', 'caption', 'expires_at']);
    foreach ($expired as $story) {
        echo "- ID: {$story->id}, Caption: '{$story->caption}', Expired: {$story->expires_at}\n";
    }

    echo "\n🎯 Now you can test the cleanup functionality!\n";
    echo "1. Go to /admin/stories in your browser\n";
    echo "2. Click the 'Cleanup Expired' button\n";
    echo "3. Confirm the action\n";
    echo "4. The {$expiredStories} expired stories should be deleted\n";
    echo "5. The {$activeStories} active stories should remain\n";
} else {
    echo "❌ ERROR: No expired stories were created!\n";
}

echo "\n✅ Test setup completed!\n";
