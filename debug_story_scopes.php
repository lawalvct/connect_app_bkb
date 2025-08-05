<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Carbon\Carbon;

echo "=== Story Scope Debug ===" . PHP_EOL;

// Basic counts
$totalCount = Story::count();
echo "Total stories: {$totalCount}" . PHP_EOL;

// Test scopes
$expiredCount = Story::expired()->count();
echo "Expired stories (using scope): {$expiredCount}" . PHP_EOL;

$activeCount = Story::active()->count();
echo "Active stories (using scope): {$activeCount}" . PHP_EOL;

// Raw query test
$rawExpired = Story::whereRaw('expires_at <= NOW()')->count();
echo "Raw expired query (expires_at <= NOW()): {$rawExpired}" . PHP_EOL;

// Using Carbon now()
$carbonExpired = Story::where('expires_at', '<=', Carbon::now())->count();
echo "Carbon expired query: {$carbonExpired}" . PHP_EOL;

// Debug specific records
echo PHP_EOL . "=== Sample Records ===" . PHP_EOL;
$stories = Story::orderBy('created_at', 'desc')->limit(5)->get(['id', 'expires_at', 'created_at']);
foreach ($stories as $story) {
    $now = Carbon::now();
    $isExpired = $story->expires_at <= $now;
    echo "Story {$story->id}: expires_at={$story->expires_at}, now={$now}, expired=" . ($isExpired ? 'YES' : 'NO') . PHP_EOL;
}

// Check scope SQL
echo PHP_EOL . "=== Scope SQL Debug ===" . PHP_EOL;
$expiredQuery = Story::expired();
echo "Expired scope SQL: " . $expiredQuery->toSql() . PHP_EOL;
echo "Expired scope bindings: " . json_encode($expiredQuery->getBindings()) . PHP_EOL;

$activeQuery = Story::active();
echo "Active scope SQL: " . $activeQuery->toSql() . PHP_EOL;
echo "Active scope bindings: " . json_encode($activeQuery->getBindings()) . PHP_EOL;

echo PHP_EOL . "=== Timezone Info ===" . PHP_EOL;
echo "PHP timezone: " . date_default_timezone_get() . PHP_EOL;
echo "Laravel timezone: " . config('app.timezone') . PHP_EOL;
echo "Carbon now(): " . Carbon::now() . PHP_EOL;
echo "Carbon now() UTC: " . Carbon::now('UTC') . PHP_EOL;
echo "Database NOW(): ";
$dbNow = \DB::select('SELECT NOW() as now')[0]->now;
echo $dbNow . PHP_EOL;
