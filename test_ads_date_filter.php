<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdManagementController;

echo "=== Testing Ads Date Range Filter ===" . PHP_EOL;

// Create a mock request with date_from and date_to parameters
$request = new Request([
    'date_from' => '2025-08-01',
    'date_to' => '2025-08-06',
    'page' => 1
]);

$controller = new AdManagementController();

try {
    $response = $controller->getAds($request);
    $data = $response->getData(true);

    echo "✅ API Response successful" . PHP_EOL;
    echo "Success: " . ($data['success'] ? 'true' : 'false') . PHP_EOL;
    echo "Total ads found: " . ($data['ads']['total'] ?? 'N/A') . PHP_EOL;
    echo "Current page: " . ($data['ads']['current_page'] ?? 'N/A') . PHP_EOL;
    echo "Ads in this page: " . count($data['ads']['data'] ?? []) . PHP_EOL;

    if (!empty($data['ads']['data'])) {
        echo PHP_EOL . "Sample ad dates:" . PHP_EOL;
        foreach (array_slice($data['ads']['data'], 0, 3) as $ad) {
            echo "- Ad ID {$ad['id']}: {$ad['ad_name']} - {$ad['created_at']}" . PHP_EOL;
        }
    }

} catch (\Exception $e) {
    echo "❌ Error testing date filter: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
