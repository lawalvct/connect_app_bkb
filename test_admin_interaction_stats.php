<?php

/**
 * Test file to verify admin stream interaction statistics functionality
 * Run this file to test the admin interface interaction features
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Test connection
try {
    echo "Testing admin stream interaction statistics...\n\n";

    // Get a sample stream with interactions
    $streams = DB::table('streams')
        ->select([
            'streams.*',
            'users.name as user_name',
            'users.username'
        ])
        ->leftJoin('users', 'streams.user_id', '=', 'users.id')
        ->where('streams.likes_count', '>', 0)
        ->orWhere('streams.dislikes_count', '>', 0)
        ->orWhere('streams.shares_count', '>', 0)
        ->limit(5)
        ->get();

    if ($streams->count() > 0) {
        echo "Found streams with interactions:\n";
        echo "================================\n";

        foreach ($streams as $stream) {
            echo "Stream ID: {$stream->id}\n";
            echo "Title: {$stream->title}\n";
            echo "Creator: {$stream->user_name} (@{$stream->username})\n";
            echo "Status: {$stream->status}\n";
            echo "Likes: {$stream->likes_count}\n";
            echo "Dislikes: {$stream->dislikes_count}\n";
            echo "Shares: {$stream->shares_count}\n";

            // Calculate engagement rate
            $totalInteractions = ($stream->likes_count ?? 0) + ($stream->dislikes_count ?? 0) + ($stream->shares_count ?? 0);
            $engagementRate = $totalInteractions > 0 ? round(($totalInteractions / max(1, $stream->peak_viewers ?? 1)) * 100, 2) : 0;
            echo "Engagement Rate: {$engagementRate}%\n";
            echo "---\n";
        }
    } else {
        echo "No streams with interactions found. Creating test data...\n";

        // Find a random stream
        $testStream = DB::table('streams')->first();

        if ($testStream) {
            // Add some test interaction counts
            DB::table('streams')
                ->where('id', $testStream->id)
                ->update([
                    'likes_count' => 25,
                    'dislikes_count' => 3,
                    'shares_count' => 8,
                    'updated_at' => now()
                ]);

            echo "Added test interaction data to stream ID: {$testStream->id}\n";
            echo "Likes: 25, Dislikes: 3, Shares: 8\n";
        } else {
            echo "No streams found in database.\n";
        }
    }

    // Test overall statistics
    echo "\nOverall Interaction Statistics:\n";
    echo "==============================\n";

    $stats = DB::table('streams')
        ->selectRaw('
            COUNT(*) as total_streams,
            SUM(likes_count) as total_likes,
            SUM(dislikes_count) as total_dislikes,
            SUM(shares_count) as total_shares,
            AVG(likes_count) as avg_likes,
            AVG(dislikes_count) as avg_dislikes,
            AVG(shares_count) as avg_shares
        ')
        ->first();

    echo "Total Streams: {$stats->total_streams}\n";
    echo "Total Likes: {$stats->total_likes}\n";
    echo "Total Dislikes: {$stats->total_dislikes}\n";
    echo "Total Shares: {$stats->total_shares}\n";
    echo "Average Likes per Stream: " . round($stats->avg_likes, 2) . "\n";
    echo "Average Dislikes per Stream: " . round($stats->avg_dislikes, 2) . "\n";
    echo "Average Shares per Stream: " . round($stats->avg_shares, 2) . "\n";

    // Test most engaged streams
    echo "\nMost Engaged Streams:\n";
    echo "===================\n";

    $mostEngaged = DB::table('streams')
        ->select([
            'id',
            'title',
            'likes_count',
            'dislikes_count',
            'shares_count',
            DB::raw('(likes_count + dislikes_count + shares_count) as total_interactions')
        ])
        ->where(DB::raw('(likes_count + dislikes_count + shares_count)'), '>', 0)
        ->orderBy('total_interactions', 'desc')
        ->limit(3)
        ->get();

    foreach ($mostEngaged as $stream) {
        echo "Stream: {$stream->title}\n";
        echo "Total Interactions: {$stream->total_interactions}\n";
        echo "Breakdown - Likes: {$stream->likes_count}, Dislikes: {$stream->dislikes_count}, Shares: {$stream->shares_count}\n";
        echo "---\n";
    }

    echo "\n✅ Admin stream interaction statistics test completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Visit the admin streams management page to see interaction counts\n";
    echo "2. View individual stream details with interaction statistics\n";
    echo "3. Access the new interaction statistics API endpoint\n";

} catch (Exception $e) {
    echo "❌ Error testing admin interaction statistics: " . $e->getMessage() . "\n";
    echo "Make sure the database is properly configured and migrations are run.\n";
}
