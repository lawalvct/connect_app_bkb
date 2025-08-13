<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "Checking users in database:\n";

try {
    $users = \App\Models\User::select('id', 'name', 'email')->take(5)->get();
    echo "Total users: " . \App\Models\User::count() . "\n";

    if ($users->count() > 0) {
        echo "First 5 users:\n";
        foreach ($users as $user) {
            echo "ID: {$user->id} - {$user->name} ({$user->email})\n";
        }
    } else {
        echo "No users found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
