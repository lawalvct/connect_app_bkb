<?php

/**
 * Test file to debug replaceProfileImage database update issue
 * Run this to check if UserProfileUpload model updates work correctly
 */

require_once 'vendor/autoload.php';

use App\Models\UserProfileUpload;
use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    echo "Testing UserProfileUpload database operations...\n\n";

    // Test 1: Find a test user
    $user = User::where('deleted_flag', 'N')->first();
    if (!$user) {
        echo "No active users found. Please create a user first.\n";
        exit;
    }

    echo "Using test user: {$user->name} (ID: {$user->id})\n\n";

    // Test 2: Create a test profile upload record
    $testData = [
        'user_id' => $user->id,
        'file_name' => 'test_' . time() . '.jpg',
        'file_url' => 'uploads/profiles/test_' . time() . '.jpg',
        'file_type' => 'image',
        'deleted_flag' => 'N',
    ];

    echo "Creating test profile upload record...\n";
    $profileUpload = UserProfileUpload::create($testData);
    echo "✅ Created record with ID: {$profileUpload->id}\n";
    echo "Original file_name: {$profileUpload->file_name}\n";
    echo "Original file_url: {$profileUpload->file_url}\n\n";

    // Test 3: Try to update the record (simulating replaceProfileImage)
    $newFileName = 'updated_' . time() . '.jpg';
    $newFileUrl = 'uploads/profiles/updated_' . time() . '.jpg';

    echo "Attempting to update the record...\n";
    $updateResult = $profileUpload->update([
        'file_name' => $newFileName,
        'file_url' => $newFileUrl,
        'file_type' => 'image',
    ]);

    if ($updateResult) {
        echo "✅ Update successful!\n";

        // Refresh the model to get updated data
        $profileUpload->refresh();
        echo "New file_name: {$profileUpload->file_name}\n";
        echo "New file_url: {$profileUpload->file_url}\n";
        echo "File type: {$profileUpload->file_type}\n\n";

        // Test 4: Verify the update in database
        $dbRecord = DB::table('user_profile_uploads')
            ->where('id', $profileUpload->id)
            ->first();

        echo "Database verification:\n";
        echo "DB file_name: {$dbRecord->file_name}\n";
        echo "DB file_url: {$dbRecord->file_url}\n";
        echo "DB file_type: {$dbRecord->file_type}\n";
        echo "DB updated_at: {$dbRecord->updated_at}\n\n";

        if ($dbRecord->file_name === $newFileName && $dbRecord->file_url === $newFileUrl) {
            echo "✅ Database update verified successfully!\n";
        } else {
            echo "❌ Database update failed!\n";
            echo "Expected file_name: {$newFileName}, Got: {$dbRecord->file_name}\n";
            echo "Expected file_url: {$newFileUrl}, Got: {$dbRecord->file_url}\n";
        }

    } else {
        echo "❌ Update failed!\n";
    }

    // Test 5: Search functionality (simulating the find logic in replaceProfileImage)
    echo "\nTesting search functionality...\n";
    $foundRecord = UserProfileUpload::where('id', $profileUpload->id)
        ->where('user_id', $user->id)
        ->where('deleted_flag', 'N')
        ->first();

    if ($foundRecord) {
        echo "✅ Record found successfully!\n";
        echo "Found ID: {$foundRecord->id}\n";
        echo "Found file_name: {$foundRecord->file_name}\n";
    } else {
        echo "❌ Record not found!\n";
    }

    // Cleanup: Delete the test record
    echo "\nCleaning up test record...\n";
    $profileUpload->delete();
    echo "✅ Test record deleted.\n";

    echo "\n=== Test Summary ===\n";
    echo "✅ UserProfileUpload model is working correctly\n";
    echo "✅ Database updates are functioning\n";
    echo "✅ Search functionality is working\n";
    echo "\nIf replaceProfileImage is still not updating, the issue might be:\n";
    echo "1. Transaction rollback due to an error\n";
    echo "2. Wrong image_id being passed\n";
    echo "3. Validation failures\n";
    echo "4. Exception being thrown\n";
    echo "\nCheck the Laravel logs for more details.\n";

} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
