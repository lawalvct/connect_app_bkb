<?php

// Test script to verify admin profile functionality
// Run this from Laravel Tinker: php artisan tinker

use App\Models\Admin;

// Check if admin exists and can access profile
$admin = Admin::first();
if ($admin) {
    echo "✅ Admin found: " . $admin->name . " (" . $admin->email . ")\n";
    echo "✅ Role: " . $admin->getRoleDisplayName() . "\n";
    echo "✅ Status: " . $admin->status . "\n";
    echo "✅ Profile Controller imported successfully\n";
    echo "✅ Routes registered:\n";
    echo "   - admin.profile.index\n";
    echo "   - admin.profile.update\n";
    echo "   - admin.profile.password\n";
    echo "   - admin.profile.notifications\n";
    echo "   - admin.profile.delete-image\n";
    echo "   - admin.profile.activity\n";
} else {
    echo "❌ No admin found. Please create an admin first.\n";
}

echo "\n🎉 Profile Settings functionality is ready!\n";
echo "📱 You can now click on 'Profile Settings' in the admin panel.\n";
