<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\V1\StorePostRequest;
use App\Http\Requests\V1\UpdatePostRequest;
use App\Http\Requests\V1\StoreCommentRequest;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\PostReport;
use App\Models\PostView;
use App\Models\PostShare;
use App\Models\BlockUser;
use App\Services\MediaProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PostController extends BaseController
{
    protected $successStatus = 200;
    protected $mediaService;

    public function __construct(MediaProcessingService $mediaService)
    {
        $this->mediaService = $mediaService;
    }


    /**
     * Get feed posts with intelligent content mixing
     * Page 1: 20 random recent posts (1-3 days) + 30 mixed content
     * Other pages: Standard chronological feed with variety
     */
    public function getFeed(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $page = $request->get('page', 1);
            $perPage = 50;

            // Get blocked user IDs
            $blockedUserIds = BlockUser::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->pluck('block_user_id')
                ->toArray();

            $feedPosts = collect();
            $processedPostIds = [];

            if ($page === 1) {
                // PAGE 1: Special mixed content strategy

                // 1. First 20: Random recent posts (1-3 days old)
                $recentPosts = Post::with($this->getPostRelations($user))
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->published()
                    ->whereBetween('published_at', [
                        now()->subDays(3),
                        now()
                    ])
                    ->inRandomOrder()
                    ->limit(20)
                    ->get();

                $feedPosts = $feedPosts->concat($recentPosts);
                $processedPostIds = $recentPosts->pluck('id')->toArray();

                // 2. Next 10: Trending posts (most reactions in last 7 days)
                $trendingPosts = Post::with($this->getPostRelations($user))
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->whereNotIn('id', $processedPostIds)
                    ->published()
                    ->where('published_at', '>=', now()->subDays(7))
                    ->withCount('reactions')
                    ->orderBy('reactions_count', 'desc')
                    ->limit(10)
                    ->get();

                $feedPosts = $feedPosts->concat($trendingPosts);
                $processedPostIds = array_merge($processedPostIds, $trendingPosts->pluck('id')->toArray());

                // 3. Next 10: Posts from users you interact with most
                $interactionPosts = $this->getPostsFromFrequentInteractions($user, $blockedUserIds, $processedPostIds, 10);
                $feedPosts = $feedPosts->concat($interactionPosts);
                $processedPostIds = array_merge($processedPostIds, $interactionPosts->pluck('id')->toArray());

                // 4. Last 10: Older posts you might have missed (4-30 days old)
                $missedPosts = Post::with($this->getPostRelations($user))
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->whereNotIn('id', $processedPostIds)
                    ->published()
                    ->whereBetween('published_at', [
                        now()->subDays(30),
                        now()->subDays(4)
                    ])
                    ->inRandomOrder()
                    ->limit(10)
                    ->get();

                $feedPosts = $feedPosts->concat($missedPosts);

            } else {
                // OTHER PAGES: Standard chronological with variety
                $offset = ($page - 1) * $perPage;

                // Mix of recent and older posts
                $recentCount = 30; // 60% recent
                $olderCount = 20;  // 40% older

                // Recent posts (last 7 days)
                $recentPosts = Post::with($this->getPostRelations($user))
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->published()
                    ->where('published_at', '>=', now()->subDays(7))
                    ->latest('published_at')
                    ->skip($offset)
                    ->take($recentCount)
                    ->get();

                $feedPosts = $feedPosts->concat($recentPosts);

                // Older posts (7-60 days)
                $olderPosts = Post::with($this->getPostRelations($user))
                    ->whereNotIn('user_id', $blockedUserIds)
                    ->published()
                    ->whereBetween('published_at', [
                        now()->subDays(60),
                        now()->subDays(7)
                    ])
                    ->inRandomOrder()
                    ->limit($olderCount)
                    ->get();

                $feedPosts = $feedPosts->concat($olderPosts);
            }

            // Transform posts with user interaction data
            $feedPosts = $feedPosts->map(function ($post) use ($user) {
                $post->reaction_counts = $post->getReactionCounts();
                $post->user_reaction = $post->getUserReaction($user->id);
                $post->has_user_liked = $post->hasUserLiked($user->id);
                return $post;
            });

            // Calculate total for pagination
            $total = Post::whereNotIn('user_id', $blockedUserIds)
                ->published()
                ->count();

            return response()->json([
                'status' => 1,
                'message' => 'Feed retrieved successfully',
                'data' => $feedPosts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => (($page - 1) * $perPage) + 1,
                    'to' => min($page * $perPage, $total),
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Feed retrieval failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve feed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get posts from users the current user interacts with frequently
     */
    private function getPostsFromFrequentInteractions($user, $blockedUserIds, $excludePostIds, $limit)
    {
        // Get users current user has liked/commented on most
        $frequentUserIds = DB::table('post_reactions')
            ->select('posts.user_id', DB::raw('COUNT(*) as interaction_count'))
            ->join('posts', 'post_reactions.post_id', '=', 'posts.id')
            ->where('post_reactions.user_id', $user->id)
            ->whereNotIn('posts.user_id', $blockedUserIds)
            ->groupBy('posts.user_id')
            ->orderBy('interaction_count', 'desc')
            ->limit(10)
            ->pluck('posts.user_id')
            ->toArray();

        if (empty($frequentUserIds)) {
            return collect();
        }

        return Post::with($this->getPostRelations($user))
            ->whereIn('user_id', $frequentUserIds)
            ->whereNotIn('id', $excludePostIds)
            ->published()
            ->where('published_at', '>=', now()->subDays(7))
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get standard post relations for queries
     */
    private function getPostRelations($user)
    {
        return [
            'user:id,name,username,profile,profile_url',
            'socialCircle:id,name,color',
            'media',
            'taggedUsers:id,name,username',
            'likes' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ];
    }


    /**
     * Get feed posts for user's social circles
     */
    public function getFeedOld(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Get blocked user IDs
            $blockedUserIds = BlockUser::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->pluck('block_user_id')
                ->toArray();

            // Get user's social circles
         //   $userCircles = $user->socialCircles()->pluck('social_circles.id');

            $posts = Post::with([
                'user:id,name,username,profile,profile_url',
                'socialCircle:id,name,color',
                'media',
                'taggedUsers:id,name,username',
                'likes' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }
            ])
          //  ->whereIn('social_circle_id', $userCircles)
            ->whereNotIn('user_id', $blockedUserIds) // Exclude posts from blocked users
            ->published()
            ->latest('published_at')
            ->paginate($perPage);

            // Add reaction counts and user interaction data
            $posts->getCollection()->transform(function ($post) use ($user) {
                $post->reaction_counts = $post->getReactionCounts();
                $post->user_reaction = $post->getUserReaction($user->id);
                $post->has_user_liked = $post->hasUserLiked($user->id);
                return $post;
            });

            return response()->json([
                'status' => 1,
                'message' => 'Feed retrieved successfully',
                'data' => $posts
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Feed retrieval failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve feed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feed with ads integrated
     */
    public function getFeedWithAds(Request $request)
    {
        try {
            $user = $request->user();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);

            // Get blocked user IDs
            $blockedUserIds = BlockUser::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->pluck('block_user_id')
                ->toArray();

            // Get user's social circles
            $userSocialCircles = $user->socialCircles->pluck('id')->toArray();

            // Get posts for the feed (your existing logic)
            $posts = Post::whereIn('social_circle_id', $userSocialCircles)
                ->whereNotIn('user_id', $blockedUserIds) // Exclude posts from blocked users
                ->with(['user', 'socialCircle', 'media'])
                ->latest()
                ->paginate($limit);

            // Get ads for user's social circles
            $ads = AdHelper::getUserVisibleAds($user->id, $userSocialCircles, 3);

            // Convert posts to array and inject ads
            $feedItems = [];
            $postArray = $posts->items();
            $adIndex = 0;

            foreach ($postArray as $index => $post) {
                // Add post
                $feedItems[] = [
                    'type' => 'post',
                    'data' => new PostResource($post)
                ];

                // Insert ad every 3 posts
                if (($index + 1) % 3 === 0 && $adIndex < count($ads)) {
                    $feedItems[] = [
                        'type' => 'ad',
                        'data' => new AdResource($ads[$adIndex])
                    ];
                    $adIndex++;
                }
            }

            return response()->json([
                'status' => 1,
                'message' => 'Feed retrieved successfully',
                'data' => [
                    'feed_items' => $feedItems,
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'total_pages' => $posts->lastPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve feed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new post
     */
    public function store(StorePostRequest $request): JsonResponse
    {

        DB::beginTransaction();

        try {
            $user = auth()->user();

            // Create post
            $post = Post::create([
                'user_id' => $user->id,
                'social_circle_id' => $request->social_circle_id,
                'content' => $request->content,
                'type' => $request->type,
                'location' => $request->location,
                'published_at' => $request->scheduled_at ? null : now(),
                'scheduled_at' => $request->scheduled_at,
                'is_published' => !$request->scheduled_at,
            ]);

            // Process and upload media files
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $index => $file) {
                    $mediaData = $this->processMediaLocal($file, $post->id);

                    $mediaRecord = $post->media()->create([
                        'type' => $mediaData['type'],
                        'file_path' => $mediaData['file_path'],
                        'file_url' => $mediaData['file_url'],
                        'original_name' => $mediaData['original_name'],
                        'file_size' => $mediaData['file_size'],
                        'mime_type' => $mediaData['mime_type'],
                        'width' => $mediaData['width'] ?? null,
                        'height' => $mediaData['height'] ?? null,
                        'duration' => $mediaData['duration'] ?? null,
                        'order' => $index + 1,
                    ]);
                }
            }

            // Tag users
            if ($request->tagged_users) {
                foreach ($request->tagged_users as $taggedUserId) {
                    $post->taggedUsers()->attach($taggedUserId, [
                        'tagged_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            // Get the post with all relationships fresh from database
            $post = Post::with([
                'user:id,name,username,profile,profile_url',
                'socialCircle:id,name,color',
                'media',
                'taggedUsers:id,name,username'
            ])->find($post->id);

            // Add avatar URL for user
            if ($post->user) {
                $post->user->avatar_url = $post->user->getAvatarUrlAttribute();
            }

            return response()->json([
                'status' => 1,
                'message' => $post->scheduled_at ? 'Post scheduled successfully' : 'Post created successfully',
                'data' => [
                    'post' => $post,
                    'media_count' => $post->media->count(),
                    'tagged_users_count' => $post->taggedUsers->count(),
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Post creation failed', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific post
     */
    public function show(Post $post): JsonResponse
    {
        try {
            $user = auth()->user();

            // Record view
            $this->recordView($post, $user);

            $post->load([
                'user:id,name,username,profile,profile_url',
                'socialCircle:id,name,color',
                'media:id,post_id,type,file_url,file_path,original_name,file_size,mime_type,width,height,duration',
                'taggedUsers:id,name,username',
                'comments.user:id,name,username,profile,profile_url',
                'comments.replies.user:id,name,username,profile,profile_url'
            ]);

            // Add avatar URL for user
            if ($post->user) {
                $post->user->avatar_url = $post->user->getAvatarUrlAttribute();
            }

            // Add user interaction data
            $post->reaction_counts = $post->getReactionCounts();
            $post->user_reaction = $post->getUserReaction($user->id);
            $post->has_user_liked = $post->hasUserLiked($user->id);

            return response()->json([
                'status' => 1,
                'message' => 'Post retrieved successfully',
                'data' => [
                    'post' => $post,
                    'stats' => [
                        'views' => $post->views_count ?? 0,
                        'likes' => $post->likes_count ?? 0,
                        'comments' => $post->comments_count ?? 0,
                        'shares' => $post->shares_count ?? 0,
                    ]
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Post retrieval failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve post'
            ], 500);
        }
    }

    /**
     * Update post (same day only)
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        try {
            $post->update($request->validated());
            $post->markAsEdited();

            return response()->json([
                'status' => 1,
                'message' => 'Post updated successfully',
                'data' => $post
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Post update failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to update post'
            ], 500);
        }
    }

    /**
     * Delete post
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            if ($post->user_id !== auth()->id()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Unauthorized to delete this post'
                ], 403);
            }

            // Delete media files from S3
            $mediaFiles = $post->media;
            foreach ($mediaFiles as $media) {
                $media->delete(); // This will trigger S3 deletion in model
            }

            $post->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Post deleted successfully'
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Post deletion failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to delete post'
            ], 500);
        }
    }

    /**
     * Like/Unlike or React to post
     */
    public function toggleReaction(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'reaction_type' => 'required|in:like,love,laugh,angry,sad,wow'
        ]);

        try {
            $user = auth()->user();
            $reactionType = $request->reaction_type;

            $existingReaction = PostLike::where('post_id', $post->id)
                                      ->where('user_id', $user->id)
                                      ->first();

                                      if ($existingReaction) {
                                        if ($existingReaction->reaction_type === $reactionType) {
                                            // Remove reaction
                                            $existingReaction->delete();
                                            $action = 'removed';
                                        } else {
                                            // Update reaction type
                                            $existingReaction->update(['reaction_type' => $reactionType]);
                                            $action = 'updated';
                                        }
                                    } else {
                                        // Create new reaction
                                        PostLike::create([
                                            'post_id' => $post->id,
                                            'user_id' => $user->id,
                                            'reaction_type' => $reactionType
                                        ]);
                                        $action = 'added';
                                    }

                                    // Get updated reaction counts
                                    $reactionCounts = $post->fresh()->getReactionCounts();
                                    $userReaction = $post->getUserReaction($user->id);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => "Reaction {$action} successfully",
                                        'data' => [
                                            'action' => $action,
                                            'reaction_counts' => $reactionCounts,
                                            'user_reaction' => $userReaction,
                                            'total_likes' => $post->fresh()->likes_count
                                        ]
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Reaction toggle failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to toggle reaction'
                                    ], 500);
                                }
                            }

                            /**
                             * Add comment to post
                             */
                            public function addComment(StoreCommentRequest $request, Post $post): JsonResponse
                            {
                                try {
                                    $comment = $post->comments()->create([
                                        'user_id' => auth()->id(),
                                        'parent_id' => $request->parent_id,
                                        'content' => $request->content,
                                    ]);

                                    $comment->load('user:id,name,username,profile,profile_url');

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Comment added successfully',
                                        'data' => $comment
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Comment creation failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to add comment'
                                    ], 500);
                                }
                            }

                            /**
                             * Get comments for a post
                             */
                            public function getComments(Request $request, Post $post): JsonResponse
                            {
                                try {
                                    $perPage = $request->get('per_page', 20);

                                    $comments = $post->comments()
                                                   ->with([
                                                       'user:id,name,username,profile,profile_url',
                                                       'replies.user:id,name,username,profile,profile_url'
                                                   ])
                                                   ->latest()
                                                   ->paginate($perPage);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Comments retrieved successfully',
                                        'data' => $comments
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Comments retrieval failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to retrieve comments'
                                    ], 500);
                                }
                            }

                            /**
                             * Report a post
                             */
                            public function reportPost(Request $request, Post $post): JsonResponse
                            {
                                $request->validate([
                                    'reason' => 'required|in:spam,inappropriate_content,harassment,hate_speech,violence,false_information,copyright_violation,other',
                                    'description' => 'nullable|string|max:1000'
                                ]);

                                try {
                                    // Check if user already reported this post
                                    $existingReport = PostReport::where('post_id', $post->id)
                                                               ->where('reported_by', auth()->id())
                                                               ->first();

                                    if ($existingReport) {
                                        return response()->json([
                                            'status' => 0,
                                            'message' => 'You have already reported this post'
                                        ], 400);
                                    }

                                    PostReport::create([
                                        'post_id' => $post->id,
                                        'reported_by' => auth()->id(),
                                        'reason' => $request->reason,
                                        'description' => $request->description,
                                    ]);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Post reported successfully'
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Post report failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to report post'
                                    ], 500);
                                }
                            }

                            /**
                             * Share a post
                             */
                            public function sharePost(Request $request, Post $post): JsonResponse
                            {
                                $request->validate([
                                    'shared_to' => 'required|in:external_social_media,direct_message,email,link_copy,other',
                                    'platform' => 'nullable|string'
                                ]);

                                try {
                                    PostShare::create([
                                        'post_id' => $post->id,
                                        'user_id' => auth()->id(),
                                        'shared_to' => $request->shared_to,
                                        'platform' => $request->platform,
                                    ]);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Post shared successfully',
                                        'data' => [
                                            'shares_count' => $post->fresh()->shares_count
                                        ]
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Post share failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to share post'
                                    ], 500);
                                }
                            }

                            /**
                             * Get user's own posts
                             */
                            public function getUserPosts(Request $request, $userId = null): JsonResponse
                            {
                                try {
                                    $targetUserId = $userId ?? auth()->id();
                                    $page = $request->get('page', 1);
                                    $perPage = $request->get('per_page', 20);

                                    $posts = Post::with([
                                        'user:id,name,username,profile,profile_url',
                                        'socialCircle:id,name,color',
                                        'media',
                                        'taggedUsers:id,name,username'
                                    ])
                                    ->where('user_id', $targetUserId)
                                    ->published()
                                    ->latest('published_at')
                                    ->paginate($perPage);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Posts retrieved successfully',
                                        'data' => $posts
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('User posts retrieval failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to retrieve posts'
                                    ], 500);
                                }
                            }

                            /**
                             * Get scheduled posts (user's own only)
                             */
                            public function getScheduledPosts(Request $request): JsonResponse
                            {
                                try {
                                    $posts = Post::with([
                                        'socialCircle:id,name,color',
                                        'media'
                                    ])
                                    ->where('user_id', auth()->id())
                                    ->scheduled()
                                    ->orderBy('scheduled_at')
                                    ->get();

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Scheduled posts retrieved successfully',
                                        'data' => $posts
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Scheduled posts retrieval failed', ['error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to retrieve scheduled posts'
                                    ], 500);
                                }
                            }

                            /**
                             * Publish scheduled post immediately
                             */
                            public function publishScheduledPost(Post $post): JsonResponse
                            {
                                try {
                                    if ($post->user_id !== auth()->id()) {
                                        return response()->json([
                                            'status' => 0,
                                            'message' => 'Unauthorized'
                                        ], 403);
                                    }

                                    if (!$post->is_scheduled) {
                                        return response()->json([
                                            'status' => 0,
                                            'message' => 'Post is not scheduled'
                                        ], 400);
                                    }

                                    $post->update([
                                        'is_published' => true,
                                        'published_at' => now(),
                                        'scheduled_at' => null,
                                    ]);

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Post published successfully',
                                        'data' => $post
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Post publish failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to publish post'
                                    ], 500);
                                }
                            }

                            /**
                             * Get post analytics (for post owner)
                             */
                            public function getPostAnalytics(Post $post): JsonResponse
                            {
                                try {
                                    if ($post->user_id !== auth()->id()) {
                                        return response()->json([
                                            'status' => 0,
                                            'message' => 'Unauthorized'
                                        ], 403);
                                    }

                                    $analytics = [
                                        'views' => [
                                            'total' => $post->views_count,
                                            'unique' => $post->views()->distinct('user_id')->count('user_id'),
                                            'today' => $post->views()->whereDate('viewed_at', today())->count(),
                                            'this_week' => $post->views()->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                                        ],
                                        'engagement' => [
                                            'likes_total' => $post->likes_count,
                                            'comments_total' => $post->comments_count,
                                            'shares_total' => $post->shares_count,
                                            'reaction_breakdown' => $post->getReactionCounts(),
                                        ],
                                        'reach' => [
                                            'views_by_day' => $post->views()
                                                ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
                                                ->groupBy('date')
                                                ->orderBy('date')
                                                ->take(30)
                                                ->get(),
                                        ]
                                    ];

                                    return response()->json([
                                        'status' => 1,
                                        'message' => 'Analytics retrieved successfully',
                                        'data' => $analytics
                                    ], $this->successStatus);

                                } catch (\Exception $e) {
                                    Log::error('Analytics retrieval failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                    return response()->json([
                                        'status' => 0,
                                        'message' => 'Failed to retrieve analytics'
                                    ], 500);
                                }
                            }

                            /**
                             * Record post view for analytics
                             */
                            protected function recordView(Post $post, $user): void
                            {
                                try {
                                    // Don't record view for post owner
                                    if ($post->user_id === $user->id) {
                                        return;
                                    }

                                    // Check if user already viewed today (to prevent spam)
                                    $existingView = PostView::where('post_id', $post->id)
                                                          ->where('user_id', $user->id)
                                                          ->whereDate('viewed_at', today())
                                                          ->first();

                                    if (!$existingView) {
                                        PostView::create([
                                            'post_id' => $post->id,
                                            'user_id' => $user->id,
                                            'ip_address' => request()->ip(),
                                            'user_agent' => request()->userAgent(),
                                            'viewed_at' => now(),
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    // Don't fail the request if view recording fails
                                    Log::warning('View recording failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                                }
                            }

    /**
     * Process media file locally (simplified for old project)
     */
    protected function processMediaLocal($file, $postId): array
    {
        try {
            // Create uploads/posts directory if it doesn't exist
            $uploadPath = public_path('uploads/posts');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Get file info
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Determine file type
            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoTypes = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];

            if (in_array($extension, $imageTypes)) {
                $type = 'image';
            } elseif (in_array($extension, $videoTypes)) {
                $type = 'video';
            } else {
                $type = 'file';
            }

            // Move file to uploads/posts
            $file->move($uploadPath, $filename);

            // Generate relative path and full URL
            $relativePath = 'uploads/posts/' . $filename;
            $fullUrl = url($relativePath);

            $result = [
                'type' => $type,
                'file_path' => $relativePath,
                'file_url' => $fullUrl,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType
            ];

            // For images, try to get dimensions
            if ($type === 'image') {
                try {
                    $imagePath = $uploadPath . '/' . $filename;
                    $imageSize = getimagesize($imagePath);
                    if ($imageSize) {
                        $result['width'] = $imageSize[0];
                        $result['height'] = $imageSize[1];
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not get image dimensions', ['file' => $filename, 'error' => $e->getMessage()]);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Media processing failed', [
                'file' => $originalName ?? 'unknown',
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Block a user's posts
     */
    public function blockUserPosts(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $userIdToBlock = $request->input('user_id');

            // Validate the user ID
            if (!$userIdToBlock) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User ID is required'
                ], 422);
            }

            // Check if user is trying to block themselves
            if ($userIdToBlock == $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You cannot block yourself'
                ], 422);
            }

            // Check if the user exists
            $userToBlock = \App\Models\User::find($userIdToBlock);
            if (!$userToBlock) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if already blocked
            $existingBlock = BlockUser::where('user_id', $user->id)
                ->where('block_user_id', $userIdToBlock)
                ->where('deleted_flag', 'N')
                ->first();

            if ($existingBlock) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User is already blocked'
                ], 409);
            }

            // Create block record
            BlockUser::create([
                'user_id' => $user->id,
                'block_user_id' => $userIdToBlock,
                'reason' => $request->input('reason', 'Blocked posts'),
                'created_by' => $user->id,
                'deleted_flag' => 'N'
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'User blocked successfully. You will no longer see their posts.',
                'data' => [
                    'blocked_user_id' => $userIdToBlock,
                    'blocked_user_name' => $userToBlock->name
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Block user posts failed', [
                'user_id' => auth()->id(),
                'block_user_id' => $request->input('user_id'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to block user'
            ], 500);
        }
    }

    /**
     * Unblock a user's posts
     */
    public function unblockUserPosts(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $userIdToUnblock = $request->input('user_id');

            // Validate the user ID
            if (!$userIdToUnblock) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User ID is required'
                ], 422);
            }

            // Find and soft delete the block record
            $blockRecord = BlockUser::where('user_id', $user->id)
                ->where('block_user_id', $userIdToUnblock)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$blockRecord) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User is not blocked'
                ], 404);
            }

            // Soft delete by setting deleted_flag
            $blockRecord->deleted_flag = 'Y';
            $blockRecord->updated_by = $user->id;
            $blockRecord->save();

            return response()->json([
                'status' => 1,
                'message' => 'User unblocked successfully. You will now see their posts.'
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Unblock user posts failed', [
                'user_id' => auth()->id(),
                'unblock_user_id' => $request->input('user_id'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to unblock user'
            ], 500);
        }
    }

    /**
     * Get list of blocked users
     */
    public function getBlockedUsers(): JsonResponse
    {
        try {
            $user = auth()->user();

            $blockedUsers = BlockUser::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->with('blockedUser:id,name,username,profile,profile_url')
                ->get()
                ->map(function ($block) {
                    return [
                        'id' => $block->id,
                        'blocked_user' => [
                            'id' => $block->blockedUser->id,
                            'name' => $block->blockedUser->name,
                            'username' => $block->blockedUser->username,
                            'profile' => $block->blockedUser->profile,
                            'profile_url' => $block->blockedUser->profile_url,
                        ],
                        'reason' => $block->reason,
                        'blocked_at' => $block->created_at->toDateTimeString()
                    ];
                });

            return response()->json([
                'status' => 1,
                'message' => 'Blocked users retrieved successfully',
                'data' => $blockedUsers
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get blocked users failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve blocked users'
            ], 500);
        }
    }

    /**
     * Get authenticated user's post count
     */
    public function getPostCount(): JsonResponse
    {
        try {
            $user = auth()->user();

            $totalPosts = Post::where('user_id', $user->id)
                ->where('is_published', true)
                ->count();

            $scheduledPosts = Post::where('user_id', $user->id)
                ->where('is_published', false)
                ->whereNotNull('scheduled_at')
                ->count();

            $postsThisMonth = Post::where('user_id', $user->id)
                ->where('is_published', true)
                ->whereMonth('published_at', now()->month)
                ->whereYear('published_at', now()->year)
                ->count();

            $postsThisWeek = Post::where('user_id', $user->id)
                ->where('is_published', true)
                ->whereBetween('published_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            $postsToday = Post::where('user_id', $user->id)
                ->where('is_published', true)
                ->whereDate('published_at', today())
                ->count();

            return response()->json([
                'status' => 1,
                'message' => 'Post count retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'total_posts' => $totalPosts,
                    'scheduled_posts' => $scheduledPosts,
                    'posts_this_month' => $postsThisMonth,
                    'posts_this_week' => $postsThisWeek,
                    'posts_today' => $postsToday
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get post count failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve post count'
            ], 500);
        }
    }
}
