<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Admin API Endpoint\n";
echo "=========================\n\n";

try {
    // Create a request to test the API endpoint
    $request = new \Illuminate\Http\Request();

    // Create the controller instance
    $controller = new \App\Http\Controllers\Admin\UserManagementController();

    echo "Testing getUsers API endpoint...\n";

    // Call the getUsers method
    $response = $controller->getUsers($request);

    // Get the response data
    $responseData = $response->getData(true);

    if (isset($responseData['users']) && isset($responseData['stats'])) {
        echo "✅ API response structure is correct\n\n";

        // Check users data
        $users = $responseData['users'];
        echo "Users data:\n";
        echo "- Total users returned: " . count($users['data']) . "\n";
        echo "- Pagination info: {$users['from']}-{$users['to']} of {$users['total']}\n\n";

        // Check if connection count is included in user data
        if (!empty($users['data'])) {
            $firstUser = $users['data'][0];
            echo "First user data sample:\n";
            echo "- Name: {$firstUser['name']}\n";
            echo "- Email: {$firstUser['email']}\n";
            echo "- Social circles count: " . ($firstUser['social_circles_count'] ?? 'N/A') . "\n";
            echo "- Connections count: " . ($firstUser['connections_count'] ?? 'N/A') . "\n\n";

            if (isset($firstUser['connections_count'])) {
                echo "✅ Connections count is included in user data\n";
            } else {
                echo "❌ Connections count is missing from user data\n";
            }
        }

        // Check stats data
        echo "\nStats data:\n";
        $stats = $responseData['stats'];
        foreach ($stats as $key => $value) {
            echo "- " . ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
        }

        if (isset($stats['users_with_connections'])) {
            echo "\n✅ Connection statistics are included\n";
        } else {
            echo "\n❌ Connection statistics are missing\n";
        }

    } else {
        echo "❌ API response structure is incorrect\n";
        echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error testing API: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nAPI test completed!\n";
?>
