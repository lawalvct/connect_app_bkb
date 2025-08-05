<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "Debugging Date/Time Issues\n";
echo "==========================\n\n";

$now = Carbon::now();
echo "Current Carbon now(): {$now}\n";
echo "Current Carbon now() timezone: {$now->timezone}\n";
echo "Current Carbon now() timestamp: {$now->timestamp}\n\n";

// Test past date creation
$pastDate = $now->copy()->subHours(5);
echo "Past date (5 hours ago): {$pastDate}\n";
echo "Past date timezone: {$pastDate->timezone}\n";
echo "Past date timestamp: {$pastDate->timestamp}\n";
echo "Is past date < now? " . ($pastDate < $now ? 'YES' : 'NO') . "\n";
echo "Is past date <= now? " . ($pastDate <= $now ? 'YES' : 'NO') . "\n\n";

// Test future date creation
$futureDate = $now->copy()->addHours(5);
echo "Future date (5 hours ahead): {$futureDate}\n";
echo "Future date timezone: {$futureDate->timezone}\n";
echo "Future date timestamp: {$futureDate->timestamp}\n";
echo "Is future date > now? " . ($futureDate > $now ? 'YES' : 'NO') . "\n";
echo "Is future date >= now? " . ($futureDate >= $now ? 'YES' : 'NO') . "\n\n";

// Check the current application timezone
echo "App timezone: " . config('app.timezone') . "\n";
echo "DB timezone: " . config('database.connections.mysql.timezone') . "\n";
echo "Date default timezone: " . date_default_timezone_get() . "\n\n";

// Create a simple past date manually
echo "Creating simple past date:\n";
$simplePast = Carbon::create(2025, 8, 5, 10, 0, 0); // 10 AM today
echo "Simple past date: {$simplePast}\n";
echo "Now: {$now}\n";
echo "Is simple past < now? " . ($simplePast < $now ? 'YES' : 'NO') . "\n";

echo "\nTest completed!\n";
