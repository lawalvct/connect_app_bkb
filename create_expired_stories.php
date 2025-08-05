<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== Creating Properly Expired Stories ===" . PHP_EOL;

// First, let's check database timezone
$dbTimezone = DB::select('SELECT @@session.time_zone as tz')[0]->tz;
echo "Database timezone: {$dbTimezone}" . PHP_EOL;

$dbNow = DB::select('SELECT NOW() as now')[0]->now;
echo "Database NOW(): {$dbNow}" . PHP_EOL;

$carbonNow = Carbon::now();
echo "Carbon now(): {$carbonNow}" . PHP_EOL;

echo PHP_EOL . "=== Creating Test Stories with Past Dates ===" . PHP_EOL;

// Create stories that are definitely expired (yesterday)
$yesterday = Carbon::yesterday();
$twoDaysAgo = Carbon::now()->subDays(2);
$oneHourAgo = Carbon::now()->subHour();

echo "Creating story with yesterday date: {$yesterday}" . PHP_EOL;
$story1 = Story::create([
    'user_id' => 1,
    'media_url' => 'test1.jpg',
    'media_type' => 'image',
    'expires_at' => $yesterday,
]);

echo "Creating story with 2 days ago date: {$twoDaysAgo}" . PHP_EOL;
$story2 = Story::create([
    'user_id' => 1,
    'media_url' => 'test2.jpg',
    'media_type' => 'image',
    'expires_at' => $twoDaysAgo,
]);

echo "Creating story with 1 hour ago date: {$oneHourAgo}" . PHP_EOL;
$story3 = Story::create([
    'user_id' => 1,
    'media_url' => 'test3.jpg',
    'media_type' => 'image',
    'expires_at' => $oneHourAgo,
]);

echo PHP_EOL . "=== Testing Scopes Again ===" . PHP_EOL;

$totalCount = Story::count();
echo "Total stories: {$totalCount}" . PHP_EOL;

$expiredCount = Story::expired()->count();
echo "Expired stories (using scope): {$expiredCount}" . PHP_EOL;

$activeCount = Story::active()->count();
echo "Active stories (using scope): {$activeCount}" . PHP_EOL;

// Show all stories with their expiration status
echo PHP_EOL . "=== All Stories with Expiration Status ===" . PHP_EOL;
$allStories = Story::orderBy('created_at', 'desc')->get(['id', 'expires_at', 'created_at']);
foreach ($allStories as $story) {
    $now = Carbon::now();
    $isExpired = $story->expires_at <= $now;
    echo "Story {$story->id}: expires_at={$story->expires_at}, now={$now}, expired=" . ($isExpired ? 'YES' : 'NO') . PHP_EOL;
}

// Test cleanup functionality
echo PHP_EOL . "=== Testing Cleanup Functionality ===" . PHP_EOL;
$expiredStories = Story::expired()->get();
echo "Found {$expiredStories->count()} expired stories for cleanup" . PHP_EOL;

foreach ($expiredStories as $story) {
    echo "Would delete story {$story->id} (expires_at: {$story->expires_at})" . PHP_EOL;
}
