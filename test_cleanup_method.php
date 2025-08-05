<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\StoryManagementController;
use App\Models\Story;
use Illuminate\Http\Request;

echo "Testing Story Cleanup Controller Method\n";
echo "======================================\n\n";

// Check current expired stories
$expiredCount = Story::expired()->count();
echo "Expired stories before cleanup: {$expiredCount}\n\n";

if ($expiredCount > 0) {
    // Test the controller method directly
    $controller = new StoryManagementController();
    $request = new Request();

    echo "Calling cleanupExpired() method...\n";

    try {
        $response = $controller->cleanupExpired();
        $data = json_decode($response->getContent(), true);

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response data:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        // Check counts after cleanup
        $expiredCountAfter = Story::expired()->count();
        $totalCountAfter = Story::count();

        echo "After cleanup:\n";
        echo "- Total stories: {$totalCountAfter}\n";
        echo "- Expired stories: {$expiredCountAfter}\n";

        if ($data['success']) {
            echo "\n✅ Cleanup method is working correctly!\n";
        } else {
            echo "\n❌ Cleanup method returned failure\n";
        }

    } catch (\Exception $e) {
        echo "❌ Error calling cleanup method:\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "No expired stories to cleanup.\n";
}

echo "\nTest completed!\n";
