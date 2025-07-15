<?php

namespace App\Helpers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostHelper
{
    /**
     * Get total posts count by user ID
     */
    public static function getTotalPostByUserId($userId)
    {
        try {
            return Post::where('user_id', $userId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            Log::error('Error getting total posts for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get posts by user ID with pagination
     */
    public static function getPostsByUserId($userId, $limit = 10, $offset = 0)
    {
        try {
            $posts = Post::where('user_id', $userId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->with(['media', 'socialCircle', 'user'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            // Format posts for API response
            return $posts->map(function ($post) {
                return self::formatPostForResponse($post);
            });
        } catch (\Exception $e) {
            Log::error('Error getting posts for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Format a post for API response
     */
    public static function formatPostForResponse($post)
    {
        try {
            // Get media URLs
            $mediaItems = $post->media->map(function ($media) {
                $mediaUrl = $media->file_url;
                $thumbnailUrl = $media->thumbnail_url;

                // Ensure URLs are complete
                if (!empty($mediaUrl) && !filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                    $mediaUrl = url($mediaUrl);
                }

                if (!empty($thumbnailUrl) && !filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
                    $thumbnailUrl = url($thumbnailUrl);
                }

                return [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => $mediaUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'width' => $media->width,
                    'height' => $media->height,
                    'duration' => $media->duration,
                    'order' => $media->order,
                ];
            });

            // Format social circle
            $socialCircle = null;
            if ($post->socialCircle) {
                $socialCircle = [
                    'id' => $post->socialCircle->id,
                    'name' => $post->socialCircle->name,
                    'color' => $post->socialCircle->color,
                    'icon' => $post->socialCircle->icon,
                ];
            }

            // Format user
            $user = null;
            if ($post->user) {
                $profileUrl = $post->user->profile_url;

                // Ensure profile URL is complete
                if (!empty($post->user->profile) && empty($profileUrl)) {
                    $profileUrl = url('uploads/profiles/' . $post->user->profile);
                }

                $user = [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'username' => $post->user->username,
                    'profile_url' => $profileUrl,
                ];
            }

            // Format post data
            return [
                'id' => $post->id,
                'content' => $post->content,
                'type' => $post->type,
                'location' => $post->location,
                'published_at' => $post->published_at ? $post->published_at->toISOString() : null,
                'human_time' => $post->published_at ? $post->published_at->diffForHumans() : null,
                'is_edited' => $post->is_edited,
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'shares_count' => $post->shares_count,
                'views_count' => $post->views_count,
                'media' => $mediaItems,
                'social_circle' => $socialCircle,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Error formatting post for response', [
                'post_id' => $post->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            // Return minimal post data if formatting fails
            return [
                'id' => $post->id ?? null,
                'content' => $post->content ?? null,
                'published_at' => $post->published_at ? $post->published_at->toISOString() : null,
                'error' => 'Failed to format complete post data'
            ];
        }
    }

    /**
     * Get recent posts by user ID
     */
    public static function getRecentPostsByUserId($userId, $days = 30, $limit = 10)
    {
        try {
            $dateFrom = Carbon::now()->subDays($days);

            $posts = Post::where('user_id', $userId)
                ->where('is_published', true)
                ->where('published_at', '>=', $dateFrom)
                ->whereNull('deleted_at')
                ->with(['media', 'socialCircle', 'user'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();

            // Format posts for API response
            return $posts->map(function ($post) {
                return self::formatPostForResponse($post);
            });
        } catch (\Exception $e) {
            Log::error('Error getting recent posts for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get posts by social circle
     */
    public static function getPostsBySocialCircle($socialCircleId, $limit = 20, $offset = 0)
    {
        try {
            return Post::where('social_circle_id', $socialCircleId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->with(['user', 'media', 'socialCircle'])
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting posts by social circle', [
                'social_circle_id' => $socialCircleId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get post by ID
     */
    public static function getById($postId)
    {
        try {
            return Post::where('id', $postId)
                ->whereNull('deleted_at')
                ->with(['user', 'media', 'socialCircle', 'likes', 'comments'])
                ->first();
        } catch (\Exception $e) {
            Log::error('Error getting post by ID', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create a new post
     */
    public static function create($data)
    {
        try {
            $post = new Post($data);
            $post->save();
            return $post;
        } catch (\Exception $e) {
            Log::error('Error creating post', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update post
     */
    public static function update($postId, $data)
    {
        try {
            $post = Post::find($postId);
            if ($post) {
                $post->update($data);
                return $post;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error updating post', [
                'post_id' => $postId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete post (soft delete)
     */
    public static function delete($postId)
    {
        try {
            $post = Post::find($postId);
            if ($post) {
                return $post->delete();
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting post', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user's post statistics
     */
    public static function getUserPostStats($userId)
    {
        try {
            $stats = [
                'total_posts' => 0,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => 0,
                'total_views' => 0,
                'posts_this_month' => 0,
                'posts_this_week' => 0,
                'average_likes_per_post' => 0
            ];

            $posts = Post::where('user_id', $userId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->get();

            $stats['total_posts'] = $posts->count();
            $stats['total_likes'] = $posts->sum('likes_count');
            $stats['total_comments'] = $posts->sum('comments_count');
            $stats['total_shares'] = $posts->sum('shares_count');
            $stats['total_views'] = $posts->sum('views_count');

            // Posts this month
            $stats['posts_this_month'] = Post::where('user_id', $userId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->whereMonth('published_at', Carbon::now()->month)
                ->whereYear('published_at', Carbon::now()->year)
                ->count();

            // Posts this week
            $stats['posts_this_week'] = Post::where('user_id', $userId)
                ->where('is_published', true)
                ->whereNull('deleted_at')
                ->whereBetween('published_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count();

            // Average likes per post
            if ($stats['total_posts'] > 0) {
                $stats['average_likes_per_post'] = round($stats['total_likes'] / $stats['total_posts'], 2);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting user post stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'total_posts' => 0,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => 0,
                'total_views' => 0,
                'posts_this_month' => 0,
                'posts_this_week' => 0,
                'average_likes_per_post' => 0
            ];
        }
    }

    /**
     * Get trending posts
     */
    public static function getTrendingPosts($limit = 10, $days = 7)
    {
        try {
            $dateFrom = Carbon::now()->subDays($days);

            return Post::where('is_published', true)
                ->where('published_at', '>=', $dateFrom)
                ->whereNull('deleted_at')
                ->with(['user', 'media', 'socialCircle'])
                ->orderByRaw('(likes_count + comments_count + shares_count) DESC')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting trending posts', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get posts for user's feed based on social circles
     */
    public static function getFeedPosts($userId, $socialCircleIds = [], $limit = 20, $offset = 0)
    {
        try {
            $query = Post::where('is_published', true)
                ->whereNull('deleted_at')
                ->with(['user', 'media', 'socialCircle', 'likes', 'comments']);

            if (!empty($socialCircleIds)) {
                $query->whereIn('social_circle_id', $socialCircleIds);
            }

            // Exclude user's own posts from feed (optional)
            // $query->where('user_id', '!=', $userId);

            return $query->orderBy('published_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting feed posts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get scheduled posts for a user
     */
    public static function getScheduledPosts($userId)
    {
        try {
            return Post::where('user_id', $userId)
                ->where('is_published', false)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', Carbon::now())
                ->whereNull('deleted_at')
                ->with(['media', 'socialCircle'])
                ->orderBy('scheduled_at', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting scheduled posts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Search posts
     */
    public static function searchPosts($query, $socialCircleIds = [], $limit = 20)
    {
        try {
            $searchQuery = Post::where('is_published', true)
                ->whereNull('deleted_at')
                ->where('content', 'LIKE', "%{$query}%")
                ->with(['user', 'media', 'socialCircle']);

            if (!empty($socialCircleIds)) {
                $searchQuery->whereIn('social_circle_id', $socialCircleIds);
            }

            return $searchQuery->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error searching posts', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get posts with media
     */
    public static function getPostsWithMedia($userId = null, $limit = 20)
    {
        try {
            $query = Post::where('is_published', true)
                ->whereNull('deleted_at')
                ->whereHas('media')
                ->with(['user', 'media', 'socialCircle']);

            if ($userId) {
                $query->where('user_id', $userId);
            }

            return $query->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting posts with media', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Increment post views
     */
    public static function incrementViews($postId)
    {
        try {
            return Post::where('id', $postId)->increment('views_count');
        } catch (\Exception $e) {
            Log::error('Error incrementing post views', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Increment post likes
     */
    public static function incrementLikes($postId)
    {
        try {
            return Post::where('id', $postId)->increment('likes_count');
        } catch (\Exception $e) {
            Log::error('Error incrementing post likes', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Decrement post likes
     */
    public static function decrementLikes($postId)
    {
        try {
            return Post::where('id', $postId)->decrement('likes_count');
        } catch (\Exception $e) {
            Log::error('Error decrementing post likes', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Increment post comments
     */
    public static function incrementComments($postId)
    {
        try {
            return Post::where('id', $postId)->increment('comments_count');
        } catch (\Exception $e) {
            Log::error('Error incrementing post comments', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Decrement post comments
     */
    public static function decrementComments($postId)
    {
        try {
            return Post::where('id', $postId)->decrement('comments_count');
        } catch (\Exception $e) {
            Log::error('Error decrementing post comments', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Increment post shares
     */
    public static function incrementShares($postId)
    {
        try {
            return Post::where('id', $postId)->increment('shares_count');
        } catch (\Exception $e) {
            Log::error('Error incrementing post shares', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
