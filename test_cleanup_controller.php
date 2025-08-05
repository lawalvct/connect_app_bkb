<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Http\Controllers\Admin\StoryManagementController;

echo "=== Testing Story Cleanup Controller ===" . PHP_EOL;

// Check current state
$totalBefore = Story::count();
$expiredBefore = Story::expired()->count();
echo "Before cleanup - Total: {$totalBefore}, Expired: {$expiredBefore}" . PHP_EOL;

// Create controller instance and call cleanup method
$controller = new StoryManagementController();

try {
    echo "Calling cleanupExpired method..." . PHP_EOL;
    $response = $controller->cleanupExpired();

    // Get response content
    $responseData = $response->getData(true);
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . PHP_EOL;

} catch (Exception $e) {
    echo "Error calling cleanup: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

// Check state after cleanup
$totalAfter = Story::count();
$expiredAfter = Story::expired()->count();
echo PHP_EOL . "After cleanup - Total: {$totalAfter}, Expired: {$expiredAfter}" . PHP_EOL;

echo "Stories deleted: " . ($totalBefore - $totalAfter) . PHP_EOL;
