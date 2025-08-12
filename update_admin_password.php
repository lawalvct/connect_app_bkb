<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Updating Admin Password...\n";

try {
    $admin = App\Models\Admin::first();
    echo "Updating password for: {$admin->name} ({$admin->email})\n";

    $admin->password = bcrypt('password123');
    $admin->save();

    echo "Password updated successfully!\n";
    echo "Email: {$admin->email}\n";
    echo "Password: password123\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
