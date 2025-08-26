<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Models\PostMedia;

echo "=== Media Relationship Debug ===\n";

// Check total counts
echo "Total posts: " . Post::count() . "\n";
echo "Total media: " . PostMedia::count() . "\n";
echo "Posts with media: " . Post::has('media')->count() . "\n";

// Get a post with media
$post = Post::with('media')->has('media')->first();

if ($post) {
    echo "\n=== Post Details ===\n";
    echo "Post ID: " . $post->id . "\n";
    echo "Post Content: " . substr($post->content, 0, 50) . "...\n";
    echo "Media count in relationship: " . $post->media->count() . "\n";

    if ($post->media->count() > 0) {
        echo "\n=== Media Details ===\n";
        foreach ($post->media as $media) {
            echo "Media ID: " . $media->id . "\n";
            echo "Post ID: " . $media->post_id . "\n";
            echo "File URL: " . $media->file_url . "\n";
            echo "File Path: " . $media->file_path . "\n";
            echo "---\n";
        }
    }

    // Try fresh query
    echo "\n=== Fresh Query Test ===\n";
    $freshPost = Post::find($post->id)->fresh();
    $freshPost->load('media');
    echo "Fresh post media count: " . $freshPost->media->count() . "\n";

} else {
    echo "No posts with media found!\n";
}

// Direct media query
echo "\n=== Direct Media Query ===\n";
$media = PostMedia::with('post')->get();
foreach ($media as $m) {
    echo "Media ID: " . $m->id . " belongs to Post ID: " . $m->post_id . "\n";
}

echo "\nDone.\n";
