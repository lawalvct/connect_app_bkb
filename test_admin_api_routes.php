<?php

// Include Laravel bootstrap
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing admin API routes...\n\n";

// Test routes that should exist
$routes_to_test = [
    'admin.admins.index',
    'admin.admins.create',
    'admin.admins.api.admins',
    'admin.admins.api.bulk-status'
];

foreach ($routes_to_test as $route) {
    echo "Testing route: {$route}\n";

    // Check if route exists
    if (\Illuminate\Support\Facades\Route::has($route)) {
        echo "  ✅ Route exists\n";

        try {
            $url = route($route);
            echo "  ✅ URL: {$url}\n";
        } catch (Exception $e) {
            echo "  ❌ Error generating URL: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ❌ Route does not exist\n";
    }
    echo "\n";
}

echo "Route testing complete!\n";
