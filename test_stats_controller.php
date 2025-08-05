<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\StoryManagementController;
use Illuminate\Http\Request;

echo "=== Testing StoryManagementController Stats Method ===" . PHP_EOL;

try {
    $controller = new StoryManagementController();
    $request = new Request();

    echo "Calling getStats method..." . PHP_EOL;
    $response = $controller->getStats($request);

    echo "Response status: " . $response->getStatusCode() . PHP_EOL;
    echo "Response content: " . $response->getContent() . PHP_EOL;

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
