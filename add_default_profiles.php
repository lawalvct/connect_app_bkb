<?php

/**
 * Script to add default profile uploads and images for users who don't have them
 * Run this from command line: php add_default_profiles.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserProfileUpload;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Adding Default Profiles Script ===\n\n";

// Get all active users
$users = User::where('deleted_flag', 'N')
    ->where('is_active', true)
    ->get();

$usersWithoutProfileUploads = 0;
$usersWithoutProfileImage = 0;
$usersProcessed = 0;
$errors = 0;

foreach ($users as $user) {
    try {
        $updated = false;

        // Check if user has profile uploads
        $hasProfileUploads = UserProfileUpload::where('user_id', $user->id)
            ->where('deleted_flag', 'N')
            ->exists();

        if (!$hasProfileUploads) {
            echo "User ID {$user->id} ({$user->email}) - Adding default profile uploads...\n";
            addDefaultProfileUploads($user);
            $usersWithoutProfileUploads++;
            $updated = true;
        }

        // Check if user has a profile image set
        if (empty($user->profile)) {
            echo "User ID {$user->id} ({$user->email}) - Setting default profile image...\n";

            // Set default profile image based on gender
            if ($user->gender === 'female') {
                $user->profile = 'female1.png';
            } else {
                // Default to male for male gender or any other/null
                $user->profile = 'male1.png';
            }

            $user->profile_url = 'uploads/profiles/';
            $user->save();

            $usersWithoutProfileImage++;
            $updated = true;
        }

        if ($updated) {
            $usersProcessed++;
            echo "✓ User ID {$user->id} processed successfully\n\n";
        }

    } catch (\Exception $e) {
        echo "✗ Error processing User ID {$user->id}: {$e->getMessage()}\n\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Total users checked: " . $users->count() . "\n";
echo "Users without profile uploads (fixed): {$usersWithoutProfileUploads}\n";
echo "Users without profile image (fixed): {$usersWithoutProfileImage}\n";
echo "Total users processed: {$usersProcessed}\n";
echo "Errors: {$errors}\n";
echo "\nScript completed!\n";

/**
 * Add default profile uploads to a user
 *
 * @param User $user
 * @return void
 */
function addDefaultProfileUploads($user)
{
    // Define the default avatars based on user gender
    $defaultUploads = [];

    if ($user->gender === 'female') {
        $defaultUploads = [
            [
                'file_name' => 'female1.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'female2.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'female3.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'female4.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ]
        ];
    } else {
        // Default to male images for male gender or any other gender/null
        $defaultUploads = [
            [
                'file_name' => 'male1.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'male2.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'male3.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ],
            [
                'file_name' => 'male4.png',
                'file_url' => 'uploads/profiles/',
                'file_type' => 'image'
            ]
        ];
    }

    // Insert the records
    foreach ($defaultUploads as $upload) {
        $upload['user_id'] = $user->id;
        $upload['deleted_flag'] = 'N';
        UserProfileUpload::create($upload);
    }

    echo "  → Added 4 default profile uploads for user (Gender: " . ($user->gender ?? 'unknown') . ")\n";
}
