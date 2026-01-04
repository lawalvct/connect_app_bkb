<?php

/**
 * Script to update users with NULL name to use their username as name
 *
 * Usage: php update_null_names_from_username.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Starting to update users with NULL names...\n\n";

try {
    // Find users where name is NULL but username exists
    $usersWithNullNames = User::whereNull('name')
        ->whereNotNull('username')
        // ->where('username', '!=', '')
        // ->where('deleted_flag', 'N')
        // ->whereNull('deleted_at')
        ->get();

    $count = $usersWithNullNames->count();
    echo "Found {$count} users with NULL names but valid usernames.\n\n";

    if ($count === 0) {
        echo "No users to process. Exiting.\n";
        exit(0);
    }

    $updated = 0;
    $failed = 0;

    foreach ($usersWithNullNames as $user) {
        try {
            $oldName = $user->name;
            $user->name = $user->username;
            $user->save();

            echo "✓ Updated user #{$user->id}: username '{$user->username}' -> name '{$user->name}'\n";
            $updated++;

        } catch (\Exception $e) {
            echo "✗ Failed to update user #{$user->id} (username: {$user->username}): {$e->getMessage()}\n";
            $failed++;
        }
    }

    echo "\n========================================\n";
    echo "Summary:\n";
    echo "- Total users found: {$count}\n";
    echo "- Successfully updated: {$updated}\n";
    echo "- Failed: {$failed}\n";
    echo "========================================\n";

} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\nDone!\n";
