<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\StoryManagementController;

echo "=== Testing Stories Date Range Filter ===" . PHP_EOL;

// Create a mock request with date_from and date_to parameters
$request = new Request([
    'date_from' => '2025-08-01',
    'date_to' => '2025-08-06',
    'page' => 1
]);

$controller = new StoryManagementController();

try {
    $response = $controller->getStories($request);
    $data = $response->getData(true);

    echo "✅ API Response successful" . PHP_EOL;
    echo "Total stories found: " . ($data['stories']['total'] ?? 'N/A') . PHP_EOL;
    echo "Current page: " . ($data['stories']['current_page'] ?? 'N/A') . PHP_EOL;
    echo "Stories in this page: " . count($data['stories']['data'] ?? []) . PHP_EOL;

    if (!empty($data['stories']['data'])) {
        echo PHP_EOL . "Sample story dates:" . PHP_EOL;
        foreach (array_slice($data['stories']['data'], 0, 3) as $story) {
            echo "- Story ID {$story['id']}: {$story['created_at']}" . PHP_EOL;
        }
    }

} catch (\Exception $e) {
    echo "❌ Error testing date filter: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== Testing without privacy filter ===" . PHP_EOL;

// Test that privacy filter is no longer being processed
$requestWithPrivacy = new Request([
    'privacy' => 'all_connections',
    'page' => 1
]);

try {
    $response = $controller->getStories($requestWithPrivacy);
    echo "✅ Privacy filter successfully ignored (request processed without error)" . PHP_EOL;
} catch (\Exception $e) {
    echo "❌ Error with privacy parameter: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
