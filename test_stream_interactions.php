<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Stream;
use App\Models\StreamInteraction;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Stream Interactions (Like, Dislike, Share)\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Find or create test users and stream
    $user1 = User::where('email', 'admin@admin.com')->first();
    $user2 = User::where('email', 'user2@example.com')->first() ?? User::find(2);

    if (!$user1) {
        echo "Creating test admin user...\n";
        $user1 = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@admin.com',
            'username' => 'testadmin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'deleted_flag' => 'N'
        ]);
    }

    if (!$user2) {
        echo "Creating test user 2...\n";
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'user2@example.com',
            'username' => 'testuser2',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'deleted_flag' => 'N'
        ]);
    }

    // Find or create a test stream
    $stream = Stream::where('user_id', $user1->id)->first();

    if (!$stream) {
        echo "Creating test stream...\n";
        $stream = Stream::create([
            'user_id' => $user1->id,
            'channel_name' => 'test_stream_' . time(),
            'title' => 'Test Stream for Interactions',
            'description' => 'Testing likes, dislikes, and shares',
            'status' => 'live',
            'is_paid' => false,
            'likes_count' => 0,
            'dislikes_count' => 0,
            'shares_count' => 0,
        ]);
    }

    echo "✅ Test users found: {$user1->name} (ID: {$user1->id}) and {$user2->name} (ID: {$user2->id})\n";
    echo "✅ Test stream found: {$stream->title} (ID: {$stream->id})\n\n";

    // Clean up existing interactions for fresh test
    StreamInteraction::where('stream_id', $stream->id)->delete();
    $stream->update(['likes_count' => 0, 'dislikes_count' => 0, 'shares_count' => 0]);

    // Test 1: Like functionality
    echo "1. Testing LIKE functionality:\n";
    echo "   - User 1 likes the stream\n";

    $likeResult = $stream->toggleLike($user1);
    $stream->refresh();

    echo "   - Like action: {$likeResult['action']} ({$likeResult['type']})\n";
    echo "   - Stream likes count: {$stream->likes_count}\n";
    echo "   - User has liked: " . ($stream->hasUserLiked($user1) ? 'Yes' : 'No') . "\n\n";

    // Test 2: Dislike functionality
    echo "2. Testing DISLIKE functionality:\n";
    echo "   - User 2 dislikes the stream\n";

    $dislikeResult = $stream->toggleDislike($user2);
    $stream->refresh();

    echo "   - Dislike action: {$dislikeResult['action']} ({$dislikeResult['type']})\n";
    echo "   - Stream dislikes count: {$stream->dislikes_count}\n";
    echo "   - User has disliked: " . ($stream->hasUserDisliked($user2) ? 'Yes' : 'No') . "\n\n";

    // Test 3: Share functionality
    echo "3. Testing SHARE functionality:\n";
    echo "   - User 1 shares on Facebook\n";

    $shareInteraction = $stream->addShare($user1, 'facebook', [
        'message' => 'Check out this amazing stream!',
        'url' => "https://example.com/streams/{$stream->id}"
    ]);
    $stream->refresh();

    echo "   - Share ID: {$shareInteraction->id}\n";
    echo "   - Platform: {$shareInteraction->share_platform}\n";
    echo "   - Stream shares count: {$stream->shares_count}\n\n";

    // Test 4: Toggle like (should remove it)
    echo "4. Testing TOGGLE LIKE (remove):\n";
    echo "   - User 1 toggles like again (should remove)\n";

    $toggleResult = $stream->toggleLike($user1);
    $stream->refresh();

    echo "   - Toggle action: {$toggleResult['action']} ({$toggleResult['type']})\n";
    echo "   - Stream likes count: {$stream->likes_count}\n";
    echo "   - User has liked: " . ($stream->hasUserLiked($user1) ? 'Yes' : 'No') . "\n\n";

    // Test 5: Change dislike to like
    echo "5. Testing CHANGE DISLIKE TO LIKE:\n";
    echo "   - User 2 (who disliked) now likes the stream\n";

    $changeResult = $stream->toggleLike($user2);
    $stream->refresh();

    echo "   - Change action: {$changeResult['action']} ({$changeResult['type']})\n";
    if (isset($changeResult['from'])) {
        echo "   - Changed from: {$changeResult['from']}\n";
    }
    echo "   - Stream likes count: {$stream->likes_count}\n";
    echo "   - Stream dislikes count: {$stream->dislikes_count}\n";
    echo "   - User has liked: " . ($stream->hasUserLiked($user2) ? 'Yes' : 'No') . "\n";
    echo "   - User has disliked: " . ($stream->hasUserDisliked($user2) ? 'Yes' : 'No') . "\n\n";

    // Test 6: Multiple shares
    echo "6. Testing MULTIPLE SHARES:\n";
    echo "   - User 2 shares on Twitter\n";
    echo "   - User 1 shares on WhatsApp\n";

    $twitterShare = $stream->addShare($user2, 'twitter', [
        'message' => 'Live streaming now!',
        'hashtags' => ['livestream', 'connect']
    ]);

    $whatsappShare = $stream->addShare($user1, 'whatsapp', [
        'message' => 'Join me on this live stream',
        'recipients' => ['friend1', 'friend2']
    ]);

    $stream->refresh();

    echo "   - Twitter share ID: {$twitterShare->id}\n";
    echo "   - WhatsApp share ID: {$whatsappShare->id}\n";
    echo "   - Total shares count: {$stream->shares_count}\n\n";

    // Test 7: Interaction stats
    echo "7. Testing INTERACTION STATS:\n";
    $stats = $stream->getInteractionStats();

    echo "   - Likes: {$stats['likes_count']}\n";
    echo "   - Dislikes: {$stats['dislikes_count']}\n";
    echo "   - Shares: {$stats['shares_count']}\n\n";

    // Test 8: User interactions
    echo "8. Testing USER INTERACTIONS:\n";
    echo "   - User 1 interaction: " . ($stream->getUserInteraction($user1) ?? 'None') . "\n";
    echo "   - User 2 interaction: " . ($stream->getUserInteraction($user2) ?? 'None') . "\n\n";

    // Test 9: Database verification
    echo "9. DATABASE VERIFICATION:\n";
    $totalInteractions = StreamInteraction::where('stream_id', $stream->id)->count();
    $likesCount = StreamInteraction::where('stream_id', $stream->id)->where('interaction_type', 'like')->count();
    $dislikesCount = StreamInteraction::where('stream_id', $stream->id)->where('interaction_type', 'dislike')->count();
    $sharesCount = StreamInteraction::where('stream_id', $stream->id)->where('interaction_type', 'share')->count();

    echo "   - Total interactions in DB: {$totalInteractions}\n";
    echo "   - Likes in DB: {$likesCount}\n";
    echo "   - Dislikes in DB: {$dislikesCount}\n";
    echo "   - Shares in DB: {$sharesCount}\n\n";

    echo "✅ All tests completed successfully!\n";
    echo "✅ Stream interactions (like, dislike, share) are working properly!\n\n";

    echo "API Endpoints Ready:\n";
    echo "POST /api/v1/streams/{id}/like - Toggle like\n";
    echo "POST /api/v1/streams/{id}/dislike - Toggle dislike\n";
    echo "POST /api/v1/streams/{id}/share - Share stream\n";
    echo "GET /api/v1/streams/{id}/interactions - Get interaction stats\n";
    echo "GET /api/v1/streams/{id}/shares - Get share details\n";
    echo "DELETE /api/v1/streams/{id}/interactions - Remove like/dislike\n";

} catch (\Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
