<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Carbon\Carbon;

echo "=== Testing Story Model Methods ===" . PHP_EOL;

try {
    // Test basic counts
    echo "Testing basic counts..." . PHP_EOL;
    $totalCount = Story::count();
    echo "Total stories: {$totalCount}" . PHP_EOL;

    // Test active scope
    echo "Testing active scope..." . PHP_EOL;
    $activeCount = Story::active()->count();
    echo "Active stories: {$activeCount}" . PHP_EOL;

    // Test expired scope
    echo "Testing expired scope..." . PHP_EOL;
    $expiredCount = Story::expired()->count();
    echo "Expired stories: {$expiredCount}" . PHP_EOL;

    // Test individual story accessors
    echo "Testing individual story accessors..." . PHP_EOL;
    $stories = Story::take(5)->get();

    foreach ($stories as $story) {
        echo "Story {$story->id}:" . PHP_EOL;
        echo "  expires_at: " . ($story->expires_at ? $story->expires_at : 'NULL') . PHP_EOL;

        try {
            $isExpired = $story->is_expired;
            echo "  is_expired: " . ($isExpired ? 'true' : 'false') . PHP_EOL;

            $timeLeft = $story->time_left;
            echo "  time_left: {$timeLeft}" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ERROR accessing attributes: " . $e->getMessage() . PHP_EOL;
        }
        echo PHP_EOL;
    }

    // Test loading with accessors (similar to what the controller does)
    echo "Testing story loading with appended attributes..." . PHP_EOL;
    $storiesWithAppends = Story::take(3)->get();

    foreach ($storiesWithAppends as $story) {
        echo "Story {$story->id}: " . json_encode([
            'id' => $story->id,
            'expires_at' => $story->expires_at,
            'is_expired' => $story->is_expired,
            'time_left' => $story->time_left
        ]) . PHP_EOL;
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
