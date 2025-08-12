<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Admin Users...\n";

try {
    $adminCount = App\Models\Admin::count();
    echo "Admin count: {$adminCount}\n";

    if ($adminCount > 0) {
        $admin = App\Models\Admin::first();
        echo "First admin: {$admin->name} ({$admin->email})\n";
        echo "Admin ID: {$admin->id}\n";
        echo "Admin status: " . ($admin->is_active ? 'Active' : 'Inactive') . "\n";

        if (!$admin->is_active) {
            $admin->is_active = true;
            $admin->save();
            echo "Admin activated!\n";
        }
    } else {
        echo "No admin users found. Creating one...\n";

        $admin = App\Models\Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        echo "Admin created: {$admin->name} ({$admin->email})\n";
        echo "Password: password\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
