<?php

// Simple route test
echo "Testing admin.admins.index route...\n";

try {
    // Include Laravel bootstrap
    require_once __DIR__ . '/bootstrap/app.php';

    $app = \Illuminate\Foundation\Application::getInstance();

    // Test route generation
    $url = route('admin.admins.index');
    echo "✅ Route admin.admins.index exists: " . $url . "\n";

} catch (Exception $e) {
    echo "❌ Route admin.admins.index not found: " . $e->getMessage() . "\n";
}

try {
    $url = route('admin.admins.create');
    echo "✅ Route admin.admins.create exists: " . $url . "\n";
} catch (Exception $e) {
    echo "❌ Route admin.admins.create not found: " . $e->getMessage() . "\n";
}
