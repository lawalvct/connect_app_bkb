<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

echo "=== Recent Posts with Media ===\n";
$posts = Post::with('media')->has('media')->orderBy('id', 'desc')->take(5)->get(['id', 'content']);

foreach ($posts as $post) {
    echo "Post {$post->id}: " . substr($post->content, 0, 50) . "... (media: {$post->media->count()})\n";
}
