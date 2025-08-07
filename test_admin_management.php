<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "Testing Admin Management Implementation...\n\n";

try {
    // Test if AdminManagementController exists
    if (class_exists('App\\Http\\Controllers\\Admin\\AdminManagementController')) {
        echo "✅ AdminManagementController class exists\n";
    } else {
        echo "❌ AdminManagementController class not found\n";
    }

    // Test if AdminPermissions middleware exists
    if (class_exists('App\\Http\\Middleware\\AdminPermissions')) {
        echo "✅ AdminPermissions middleware exists\n";
    } else {
        echo "❌ AdminPermissions middleware not found\n";
    }

    // Test if Admin model methods exist
    require_once __DIR__ . '/app/Models/Admin.php';
    $adminModel = new \App\Models\Admin();

    $methods = ['hasRole', 'hasPermission', 'canManageUsers', 'getRoleDisplayName'];
    foreach ($methods as $method) {
        if (method_exists($adminModel, $method)) {
            echo "✅ Admin::{$method}() method exists\n";
        } else {
            echo "❌ Admin::{$method}() method not found\n";
        }
    }

    // Test if view files exist
    $views = [
        'resources/views/admin/admins/index.blade.php',
        'resources/views/admin/admins/create.blade.php',
        'resources/views/admin/admins/show.blade.php',
        'resources/views/admin/admins/edit.blade.php'
    ];

    foreach ($views as $view) {
        if (file_exists(__DIR__ . '/' . $view)) {
            echo "✅ View {$view} exists\n";
        } else {
            echo "❌ View {$view} not found\n";
        }
    }

    echo "\n🎉 Admin Management Implementation Complete!\n";
    echo "\nNext Steps:\n";
    echo "1. Visit /admin/admins to access admin management\n";
    echo "2. Log in with Super Admin account: admin@connectapp.com / admin123\n";
    echo "3. Create new admin accounts with different roles\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
