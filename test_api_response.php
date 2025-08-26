<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

echo "=== API Response Test ===\n";

// Simulate the show method logic
$post = Post::has('media')->orderBy('id', 'desc')->first();
echo "Testing Post ID: " . $post->id . "\n";

// Load relationships like the show method
$post->load([
    'user:id,name,username,profile,profile_url',
    'socialCircle:id,name,color',
    'media:id,post_id,type,file_url,file_path,original_name,file_size,mime_type,width,height,duration',
    'taggedUsers:id,name,username'
]);

echo "Media loaded: " . $post->media->count() . " items\n";

// Check the actual response format
$responseData = [
    'post' => $post->toArray(),
    'media_count' => $post->media->count()
];

echo "\n=== Raw Media Data ===\n";
if (!empty($responseData['post']['media'])) {
    foreach ($responseData['post']['media'] as $media) {
        echo "Media Item:\n";
        foreach ($media as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
        echo "---\n";
    }
} else {
    echo "ERROR: Media array is empty in response!\n";
}

// Debug the relationship
echo "\n=== Direct Relationship Check ===\n";
$directPost = Post::find($post->id);
echo "Fresh post media count: " . $directPost->media()->count() . "\n";
$freshWithMedia = Post::with('media')->find($post->id);
echo "Fresh with media count: " . $freshWithMedia->media->count() . "\n";
