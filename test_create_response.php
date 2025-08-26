<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Models\User;

echo "=== Direct API Simulation ===\n";

// Find a post with media
$post = Post::has('media')->orderBy('id', 'desc')->first();
if (!$post) {
    echo "No posts with media found!\n";
    exit;
}

echo "Testing Post ID: " . $post->id . "\n";

// Simulate the exact logic from PostController create method response
try {
    // Get fresh post with relationships (exactly like create method does)
    $freshPost = Post::with([
        'user:id,name,username,profile,profile_url',
        'socialCircle:id,name,color',
        'media:id,post_id,type,file_url,file_path,original_name,file_size,width,height',
        'taggedUsers:id,name,username'
    ])->find($post->id);

    if ($freshPost) {
        // Prepare response exactly like the create method
        $responseData = [
            'status' => 1,
            'message' => 'Post created successfully',
            'data' => [
                'post' => $freshPost->toArray(),
                'media_count' => $freshPost->media->count(),
                'stats' => [
                    'views' => $freshPost->views_count ?? 0,
                    'likes' => $freshPost->likes_count ?? 0,
                    'comments' => $freshPost->comments_count ?? 0,
                    'shares' => $freshPost->shares_count ?? 0,
                ]
            ]
        ];

        echo "Media count in response: " . $responseData['data']['media_count'] . "\n";
        echo "Media in post array: " . (isset($responseData['data']['post']['media']) ? count($responseData['data']['post']['media']) : 0) . "\n";

        if (!empty($responseData['data']['post']['media'])) {
            echo "✅ MEDIA FOUND in response!\n";
            foreach ($responseData['data']['post']['media'] as $media) {
                echo "  - {$media['type']}: {$media['file_url']}\n";
            }
        } else {
            echo "❌ MEDIA EMPTY in response!\n";
        }

        // Output part of the JSON to see the structure
        echo "\n=== JSON Response Preview ===\n";
        $json = json_encode($responseData, JSON_PRETTY_PRINT);
        echo substr($json, 0, 800) . "...\n";

    } else {
        echo "❌ Could not load fresh post!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
