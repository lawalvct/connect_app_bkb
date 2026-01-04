<?php

/**
 * Script to add users without social circles to "Just Connect" (ID: 26)
 *
 * Usage: php add_users_to_just_connect.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\UserSocialCircle;
use Illuminate\Support\Facades\DB;

echo "Starting to add users to Just Connect social circle...\n\n";

try {
    // Find users who don't have any social circles
    $usersWithoutCircles = User::whereDoesntHave('socialCircles')
        ->where('deleted_flag', 'N')
        ->whereNull('deleted_at')
        ->where('is_active', true)
        ->get();

    $count = $usersWithoutCircles->count();
    echo "Found {$count} users without any social circles.\n\n";

    if ($count === 0) {
        echo "No users to process. Exiting.\n";
        exit(0);
    }

    $added = 0;
    $failed = 0;

    foreach ($usersWithoutCircles as $user) {
        try {
            // Check if a deleted relationship exists
            $existing = UserSocialCircle::withTrashed()
                ->where('user_id', $user->id)
                ->where('social_id', 26)
                ->first();

            if ($existing) {
                // Restore the deleted relationship
                $existing->deleted_flag = 'N';
                $existing->deleted_at = null;
                $existing->deleted_by = null;
                $existing->updated_at = now();
                $existing->save();

                // If it's soft deleted, restore it
                if ($existing->trashed()) {
                    $existing->restore();
                }

                echo "✓ Restored user #{$user->id} ({$user->name}) to Just Connect\n";
            } else {
                // Create new relationship
                UserSocialCircle::create([
                    'user_id' => $user->id,
                    'social_id' => 26,
                    'deleted_flag' => 'N'
                ]);

                echo "✓ Added user #{$user->id} ({$user->name}) to Just Connect\n";
            }

            $added++;

        } catch (\Exception $e) {
            echo "✗ Failed to add user #{$user->id} ({$user->name}): {$e->getMessage()}\n";
            $failed++;
        }
    }

    echo "\n========================================\n";
    echo "Summary:\n";
    echo "- Total users found: {$count}\n";
    echo "- Successfully added: {$added}\n";
    echo "- Failed: {$failed}\n";
    echo "========================================\n";

} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\nDone!\n";
