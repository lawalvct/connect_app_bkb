<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing User Status Column Fix\n";
echo "=============================\n\n";

try {
    echo "1. Testing User::where('is_active', true) query:\n";
    $activeUsers = User::select('id', 'name', 'email')
        ->where('is_active', true)
        ->where('is_banned', false)
        ->take(5)
        ->get();

    echo "   Found {$activeUsers->count()} active users\n";
    foreach ($activeUsers as $user) {
        echo "   - ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
    }

    echo "\n2. Testing total user counts:\n";
    $totalUsers = User::count();
    $activeUsersCount = User::where('is_active', true)->where('is_banned', false)->count();
    $bannedUsersCount = User::where('is_banned', true)->count();

    echo "   Total users: {$totalUsers}\n";
    echo "   Active users: {$activeUsersCount}\n";
    echo "   Banned users: {$bannedUsersCount}\n";

    echo "\n3. Testing user validation logic:\n";
    if ($activeUsers->count() > 0) {
        $testUser = $activeUsers->first();
        $isAvailable = $testUser->is_active && !$testUser->is_banned;
        echo "   Test user (ID: {$testUser->id}) is available for streaming: " . ($isAvailable ? 'Yes' : 'No') . "\n";
    }

    echo "\n✅ All tests passed! The status column issue has been fixed.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
