<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Post;
use App\Models\User;

echo "Testing Analytics Controller fixes...\n\n";

try {
    // Test Post count without deleted_flag
    $totalPosts = Post::count();
    echo "✅ Post::count() works: {$totalPosts} posts\n";

    // Test Post with content type query
    $contentTypes = Post::selectRaw('
            CASE
                WHEN type = "video" THEN "Video"
                WHEN type = "image" THEN "Image"
                WHEN type = "text" THEN "Text"
                ELSE "Other"
            END as content_type,
            COUNT(*) as count
        ')
        ->groupBy('content_type')
        ->get();
    echo "✅ Content types query works: " . $contentTypes->count() . " types found\n";

    // Test User with posts count
    $activeUsers = User::where('deleted_flag', 'N')
        ->withCount('posts')
        ->limit(5)
        ->get();
    echo "✅ User withCount posts works: " . $activeUsers->count() . " users found\n";

    // Test engagement stats
    $totalLikes = Post::sum('likes_count');
    echo "✅ Post likes sum works: {$totalLikes} total likes\n";

    echo "\n🎉 All analytics queries are working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
