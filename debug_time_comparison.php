<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Carbon\Carbon;

echo "=== Debugging Time Comparison ===" . PHP_EOL;

$story = Story::first();
if ($story) {
    echo "Story {$story->id}:" . PHP_EOL;
    echo "expires_at: {$story->expires_at}" . PHP_EOL;
    echo "expires_at type: " . gettype($story->expires_at) . PHP_EOL;
    echo "expires_at class: " . get_class($story->expires_at) . PHP_EOL;

    $now = now();
    echo "now(): {$now}" . PHP_EOL;
    echo "now() type: " . gettype($now) . PHP_EOL;
    echo "now() class: " . get_class($now) . PHP_EOL;

    echo "expires_at->isPast(): " . ($story->expires_at->isPast() ? 'true' : 'false') . PHP_EOL;
    echo "expires_at < now(): " . ($story->expires_at < $now ? 'true' : 'false') . PHP_EOL;
    echo "expires_at <= now(): " . ($story->expires_at <= $now ? 'true' : 'false') . PHP_EOL;

    $diffInSeconds = $story->expires_at->diffInSeconds($now, false);
    echo "diffInSeconds (false): {$diffInSeconds}" . PHP_EOL;

    $diffInSecondsSigned = $story->expires_at->diffInSeconds($now);
    echo "diffInSeconds (unsigned): {$diffInSecondsSigned}" . PHP_EOL;

    echo "is_expired accessor: " . ($story->is_expired ? 'true' : 'false') . PHP_EOL;
    echo "time_left accessor: {$story->time_left}" . PHP_EOL;
}

// Test the scopes directly
echo PHP_EOL . "=== Testing Scopes Directly ===" . PHP_EOL;
echo "Raw active query: " . Story::active()->toSql() . PHP_EOL;
echo "Raw expired query: " . Story::expired()->toSql() . PHP_EOL;

$activeBindings = Story::active()->getBindings();
$expiredBindings = Story::expired()->getBindings();

echo "Active bindings: " . json_encode($activeBindings) . PHP_EOL;
echo "Expired bindings: " . json_encode($expiredBindings) . PHP_EOL;
