<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Carbon\Carbon;

echo "=== Fixing Stories with Null Expiration Dates ===" . PHP_EOL;

// Find stories with null expires_at
$storiesWithNullExpiration = Story::whereNull('expires_at')->get();

echo "Found {$storiesWithNullExpiration->count()} stories with null expiration dates" . PHP_EOL;

if ($storiesWithNullExpiration->count() > 0) {
    foreach ($storiesWithNullExpiration as $story) {
        // Set expiration to 24 hours from the story's creation date
        $expiration = $story->created_at->addHours(24);
        $story->update(['expires_at' => $expiration]);

        echo "Updated story {$story->id}: expires_at set to {$expiration}" . PHP_EOL;
    }

    echo "All stories have been updated!" . PHP_EOL;
} else {
    echo "No stories need updating." . PHP_EOL;
}

// Verify the fix
echo PHP_EOL . "=== Verification ===" . PHP_EOL;
$totalStories = Story::count();
$activeStories = Story::active()->count();
$expiredStories = Story::expired()->count();
$nullExpirationStories = Story::whereNull('expires_at')->count();

echo "Total stories: {$totalStories}" . PHP_EOL;
echo "Active stories: {$activeStories}" . PHP_EOL;
echo "Expired stories: {$expiredStories}" . PHP_EOL;
echo "Stories with null expiration: {$nullExpirationStories}" . PHP_EOL;
