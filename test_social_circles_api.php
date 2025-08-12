<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Social Circles API ===\n";

try {
    // Use Laravel's HTTP client or just simulate the controller call
    $controller = new \App\Http\Controllers\Admin\UserManagementController();

    echo "Calling getSocialCircles method directly...\n";

    // Create a mock request
    $request = new \Illuminate\Http\Request();

    // Call the method
    $response = $controller->getSocialCircles($request);

    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content:\n";
    echo $response->getContent() . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
