<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;

echo "Creating Working Test Stories\n";
echo "============================\n\n";

$user = User::first();
$now = Carbon::now();

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Current time: {$now}\n\n";

// Clear existing stories
Story::query()->delete();

// Create truly expired stories
echo "Creating expired stories with explicit past dates...\n";

// Story 1: Expired 2 hours ago
$expired1 = $now->copy()->subHours(2);
$story1 = Story::create([
    'user_id' => $user->id,
    'type' => 'text',
    'content' => 'Expired content 1',
    'caption' => 'Expired Story 1',
    'privacy' => 'all_connections',
    'allow_replies' => true,
    'views_count' => 5,
    'expires_at' => $expired1,
]);
echo "✅ Story 1 - ID: {$story1->id}, Expires: {$expired1}, Status: EXPIRED\n";

// Story 2: Expired 6 hours ago
$expired2 = $now->copy()->subHours(6);
$story2 = Story::create([
    'user_id' => $user->id,
    'type' => 'image',
    'content' => 'Expired content 2',
    'caption' => 'Expired Story 2',
    'privacy' => 'all_connections',
    'allow_replies' => true,
    'views_count' => 10,
    'expires_at' => $expired2,
]);
echo "✅ Story 2 - ID: {$story2->id}, Expires: {$expired2}, Status: EXPIRED\n";

// Story 3: Active (expires in 3 hours)
$active1 = $now->copy()->addHours(3);
$story3 = Story::create([
    'user_id' => $user->id,
    'type' => 'video',
    'content' => 'Active content 1',
    'caption' => 'Active Story 1',
    'privacy' => 'all_connections',
    'allow_replies' => true,
    'views_count' => 15,
    'expires_at' => $active1,
]);
echo "✅ Story 3 - ID: {$story3->id}, Expires: {$active1}, Status: ACTIVE\n\n";

// Verify using the model scopes
echo "=== VERIFICATION USING MODEL SCOPES ===\n";
$totalStories = Story::count();
$activeStories = Story::active()->count();
$expiredStories = Story::expired()->count();

echo "Total stories: {$totalStories}\n";
echo "Active stories: {$activeStories}\n";
echo "Expired stories: {$expiredStories}\n\n";

// Show expired stories that would be cleaned up
if ($expiredStories > 0) {
    echo "✅ SUCCESS! Expired stories ready for cleanup:\n";
    $expired = Story::expired()->get();
    foreach ($expired as $story) {
        echo "- ID: {$story->id}, Caption: '{$story->caption}', Expires: {$story->expires_at}\n";
    }

    echo "\n🎯 READY FOR TESTING!\n";
    echo "1. Go to /admin/stories\n";
    echo "2. You should see {$totalStories} total stories\n";
    echo "3. Click 'Cleanup Expired' button\n";
    echo "4. Confirm the action\n";
    echo "5. {$expiredStories} expired stories should be deleted\n";
    echo "6. {$activeStories} active story should remain\n";
} else {
    echo "❌ No expired stories found. Something is wrong with the scope.\n";
}

echo "\n✅ Test setup completed!\n";
