<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

// Get the most recent post with media
$post = Post::with('media')->has('media')->orderBy('id', 'desc')->first();
echo 'Most recent post with media - ID: ' . $post->id . "\n";
echo 'Media count: ' . $post->media->count() . "\n";
echo 'Media details:' . "\n";
foreach ($post->media as $media) {
    echo '  - ID: ' . $media->id . ', Type: ' . $media->type . ', URL: ' . $media->file_url . "\n";
    echo '    Duration field: ' . ($media->duration ?? 'NULL') . "\n";
    echo '    Width: ' . ($media->width ?? 'NULL') . ', Height: ' . ($media->height ?? 'NULL') . "\n";
    echo '    All fields: ' . json_encode($media->toArray(), JSON_PRETTY_PRINT) . "\n";
}
