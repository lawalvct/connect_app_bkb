<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;

echo "Creating Correct Test Stories for Cleanup\n";
echo "=========================================\n\n";

$user = User::first();
$now = Carbon::now();
echo "Using user: {$user->name} (ID: {$user->id})\n";
echo "Current time: {$now}\n\n";

// Clear existing stories
echo "Clearing existing stories...\n";
Story::query()->delete();

// Create expired stories with PAST dates
echo "Creating expired stories (with past expiration dates)...\n";
for ($i = 1; $i <= 3; $i++) {
    $hoursAgo = rand(1, 48);
    $expiredAt = $now->copy()->subHours($hoursAgo); // Use copy() to avoid modifying the original $now

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
    echo "   Current time: {$now}\n";
    echo "   Is past: " . ($expiredAt->isPast() ? 'YES' : 'NO') . "\n";
    echo "   Difference: " . $expiredAt->diffForHumans($now) . "\n\n";
}

// Create active stories with FUTURE dates
echo "Creating active stories (with future expiration dates)...\n";
for ($i = 1; $i <= 2; $i++) {
    $hoursInFuture = rand(1, 24);
    $expiresAt = $now->copy()->addHours($hoursInFuture); // Use copy() to avoid modifying the original $now

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
    echo "   Current time: {$now}\n";
    echo "   Is future: " . ($expiresAt->isFuture() ? 'YES' : 'NO') . "\n";
    echo "   Difference: " . $expiresAt->diffForHumans($now) . "\n\n";
}

echo "=== FINAL VERIFICATION ===\n";
$totalStories = Story::count();
$activeStories = Story::active()->count();
$expiredStories = Story::expired()->count();

echo "Total stories: {$totalStories}\n";
echo "Active stories (expires_at > now): {$activeStories}\n";
echo "Expired stories (expires_at <= now): {$expiredStories}\n\n";

// Debug the scope
echo "=== DEBUGGING SCOPE ===\n";
$allStories = Story::all(['id', 'caption', 'expires_at']);
foreach ($allStories as $story) {
    $isExpired = $story->expires_at <= $now;
    echo "ID: {$story->id}, Expires: {$story->expires_at}, Is Expired: " . ($isExpired ? 'YES' : 'NO') . "\n";
}

if ($expiredStories > 0) {
    echo "\n✅ SUCCESS! Found {$expiredStories} expired stories ready for cleanup.\n";
    echo "\n🎯 Now test the cleanup functionality in the admin panel!\n";
} else {
    echo "\n❌ ERROR: No expired stories were created!\n";
    echo "Check the Story model's expired() scope.\n";
}

echo "\n✅ Test setup completed!\n";
