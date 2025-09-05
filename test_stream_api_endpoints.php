<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Stream;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Stream Interactions API Endpoints Test\n";
echo "=" . str_repeat("=", 40) . "\n\n";

try {
    // Get test data
    $user = User::where('email', 'admin@admin.com')->first();
    $stream = Stream::where('user_id', $user->id)->first();

    if (!$user || !$stream) {
        echo "❌ Test data not found. Please run test_stream_interactions.php first.\n";
        exit;
    }

    echo "✅ Test User: {$user->name} (ID: {$user->id})\n";
    echo "✅ Test Stream: {$stream->title} (ID: {$stream->id})\n\n";

    // Create a personal access token for API testing
    $token = $user->createToken('test-token')->plainTextToken;

    echo "🔑 Generated API Token: {$token}\n\n";

    // Base URL (adjust as needed)
    $baseUrl = "http://localhost:8000/api/v1";

    echo "📋 API Endpoints Documentation:\n";
    echo str_repeat("-", 50) . "\n\n";

    echo "1. LIKE A STREAM\n";
    echo "   Method: POST\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/like\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Response: Like status and interaction stats\n\n";

    echo "2. DISLIKE A STREAM\n";
    echo "   Method: POST\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/dislike\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Response: Dislike status and interaction stats\n\n";

    echo "3. SHARE A STREAM\n";
    echo "   Method: POST\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/share\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Body (JSON):\n";
    echo "   {\n";
    echo "       \"platform\": \"facebook\",\n";
    echo "       \"metadata\": {\n";
    echo "           \"message\": \"Check out this amazing stream!\",\n";
    echo "           \"url\": \"https://example.com/streams/{$stream->id}\"\n";
    echo "       }\n";
    echo "   }\n";
    echo "   Response: Share confirmation and updated stats\n\n";

    echo "4. GET INTERACTION STATS\n";
    echo "   Method: GET\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/interactions\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Response: Like, dislike, share counts and user's interaction\n\n";

    echo "5. GET STREAM SHARES\n";
    echo "   Method: GET\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/shares\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Response: List of shares with user details\n\n";

    echo "6. REMOVE INTERACTION\n";
    echo "   Method: DELETE\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}/interactions\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Body (JSON): {\"interaction_type\": \"like\"}\n";
    echo "   Response: Removal confirmation and updated stats\n\n";

    echo "7. GET STREAM DETAILS (includes interaction data)\n";
    echo "   Method: GET\n";
    echo "   URL: {$baseUrl}/streams/{$stream->id}\n";
    echo "   Headers: Authorization: Bearer {$token}\n";
    echo "   Response: Stream details with likes/dislikes/shares counts\n\n";

    echo "🧪 CURL Examples:\n";
    echo str_repeat("-", 30) . "\n\n";

    echo "# Like a stream\n";
    echo "curl -X POST \"{$baseUrl}/streams/{$stream->id}/like\" \\\n";
    echo "  -H \"Authorization: Bearer {$token}\" \\\n";
    echo "  -H \"Content-Type: application/json\"\n\n";

    echo "# Share a stream\n";
    echo "curl -X POST \"{$baseUrl}/streams/{$stream->id}/share\" \\\n";
    echo "  -H \"Authorization: Bearer {$token}\" \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\n";
    echo "    \"platform\": \"twitter\",\n";
    echo "    \"metadata\": {\n";
    echo "      \"message\": \"Live streaming now!\",\n";
    echo "      \"hashtags\": [\"livestream\", \"connect\"]\n";
    echo "    }\n";
    echo "  }'\n\n";

    echo "# Get interaction stats\n";
    echo "curl -X GET \"{$baseUrl}/streams/{$stream->id}/interactions\" \\\n";
    echo "  -H \"Authorization: Bearer {$token}\"\n\n";

    echo "📊 Current Stream Stats:\n";
    echo str_repeat("-", 25) . "\n";
    $stream->refresh();
    echo "   Likes: {$stream->likes_count}\n";
    echo "   Dislikes: {$stream->dislikes_count}\n";
    echo "   Shares: {$stream->shares_count}\n\n";

    echo "✅ API endpoints are ready for testing!\n";
    echo "✅ Use the token above for authentication in your requests.\n\n";

    echo "💡 Tips:\n";
    echo "   - All endpoints require authentication (Bearer token)\n";
    echo "   - Like/dislike are mutually exclusive (toggling behavior)\n";
    echo "   - Shares allow multiple entries per user\n";
    echo "   - Interaction stats include user-specific data when authenticated\n";
    echo "   - Share metadata is flexible and can include any relevant data\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
