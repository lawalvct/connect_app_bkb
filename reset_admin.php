<?php

require_once 'bootstrap/app.php';

use App\Models\Admin;

// Reset admin password for testing
$admin = Admin::first();
if ($admin) {
    $admin->password = bcrypt('admin123');
    $admin->otp_code = null;
    $admin->otp_expires_at = null;
    $admin->save();

    echo "Admin password reset successfully!\n";
    echo "Email: " . $admin->email . "\n";
    echo "Password: admin123\n";
} else {
    echo "No admin user found.\n";
}
