<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing User model and fcmTokens relationship...\n";

try {
    $user = \App\Models\User::first();
    if ($user) {
        echo "User found: " . $user->name . "\n";
        echo "FCM tokens count: " . $user->fcmTokens()->count() . "\n";
        echo "Active FCM tokens count: " . $user->activeFcmTokens()->count() . "\n";
        echo "fcmTokens method exists and works!\n";
    } else {
        echo "No users found\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
