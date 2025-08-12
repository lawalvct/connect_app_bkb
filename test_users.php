<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing User Management...\n";

try {
    $userCount = App\Models\User::count();
    echo "User count: {$userCount}\n";

    if ($userCount > 0) {
        $users = App\Models\User::take(3)->get();
        foreach ($users as $user) {
            echo "- {$user->name} ({$user->email})\n";
        }
    }

    echo "\nTesting UserManagementController...\n";
    $controller = new App\Http\Controllers\Admin\UserManagementController();
    echo "Controller created successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
