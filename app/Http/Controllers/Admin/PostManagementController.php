<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialCircle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostManagementController extends Controller
{
    /**
     * Get countries for filter dropdown
     */
    public function getCountries()
    {
        try {
            $countries = \App\Models\Country::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            return response()->json(['countries' => $countries]);
        } catch (\Exception $e) {
            return response()->json(['countries' => []], 200);
        }
    }
    /**
     * Display posts listing
     */
    public function index()
    {
        return view('admin.posts.index');
    }

    /**
     * Show post details
     */
    public function show(Post $post)
    {
        // Load relationships
        $post->load([
            'user:id,name,email,avatar',
            'socialCircle:id,name,color,logo',
            'media',
            'likes.user:id,name',
            'comments.user:id,name',
            'reports.user:id,name'
        ]);

        return view('admin.posts.show', compact('post'));
    }

    /**
     * Get posts for AJAX requests
     */
    public function getPosts(Request $request)
    {
        try {
           // Log::info('PostManagement getPosts called with params: ', $request->all());

            // Start with query
            $query = Post::query();


            // Apply filters
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('content', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('social_circle')) {
                $socialCircleId = $request->get('social_circle');
                if (is_numeric($socialCircleId)) {
                    $query->where('social_circle_id', $socialCircleId);
                }
            }

            // Country filter (by user's country)
            if ($request->filled('country')) {
                $countryId = $request->get('country');
                $query->whereHas('user', function($q) use ($countryId) {
                    $q->where('country_id', $countryId);
                });
            }

            if ($request->filled('type')) {
                $type = $request->get('type');
                if (in_array($type, ['text', 'image', 'video', 'mixed'])) {
                    $query->where('type', $type);
                }
            }

            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'published') {
                    $query->where('is_published', true);
                } elseif ($status === 'draft') {
                    $query->where('is_published', false);
                } elseif ($status === 'scheduled') {
                    $query->where('scheduled_at', '>', now());
                }
            }

            // Add date range filtering with calendar dates
            if ($request->filled('date_from')) {
                $dateFrom = $request->get('date_from');
                try {
                    $query->whereDate('created_at', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_from format: ' . $dateFrom);
                }
            }

            if ($request->filled('date_to')) {
                $dateTo = $request->get('date_to');
                try {
                    $query->whereDate('created_at', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_to format: ' . $dateTo);
                }
            }

            // Get paginated results with relationships
            $posts = $query->with([
                    'user:id,name,email,avatar,country_id',
                    'user.country:id,name,code',
                    'socialCircle:id,name,color',
                    'media:id,post_id,type,file_path,thumbnail_path'
                ])
                ->withCount(['likes', 'comments', 'reports'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Add formatted data
            $posts->getCollection()->transform(function ($post) {
                $post->created_at_human = $post->created_at->diffForHumans();
                $post->content_preview = Str::limit($post->content, 100);

                // Determine status
                if ($post->scheduled_at && $post->scheduled_at->isFuture()) {
                    $post->status = 'scheduled';
                } elseif ($post->is_published) {
                    $post->status = 'published';
                } else {
                    $post->status = 'draft';
                }

                return $post;
            });

            // Get stats
            $stats = [
                'total' => Post::count(),
                'published' => Post::where('is_published', true)->count(),
                'draft' => Post::where('is_published', false)->count(),
                'scheduled' => Post::where('scheduled_at', '>', now())->count(),
                'today' => Post::whereDate('created_at', today())->count(),
                'this_week' => Post::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_likes' => Post::sum('likes_count'),
                'total_comments' => Post::sum('comments_count'),
            ];

           // Log::info('PostManagement getPosts success - returning ' . $posts->count() . ' posts');

            return response()->json([
                'posts' => $posts,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getPosts: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Failed to load posts: ' . $e->getMessage(),
                'posts' => (object)['data' => [], 'current_page' => 1, 'last_page' => 1, 'from' => 0, 'to' => 0, 'total' => 0],
                'stats' => [
                    'total' => 0, 'published' => 0, 'draft' => 0, 'scheduled' => 0,
                    'today' => 0, 'this_week' => 0, 'total_likes' => 0, 'total_comments' => 0
                ]
            ], 200);
        }
    }

    /**
     * Get social circles for filter dropdown
     */
    public function getSocialCircles()
    {
        try {
            $socialCircles = SocialCircle::withoutGlobalScope('active')
                ->where('is_active', true)
                ->where('deleted_flag', 'N')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);

            return response()->json([
                'social_circles' => $socialCircles
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching social circles: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load social circles',
                'social_circles' => []
            ], 200);
        }
    }

    /**
     * Update post status
     */
    public function updateStatus(Request $request, Post $post)
    {
        $request->validate([
            'status' => 'required|in:published,draft,scheduled',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        try {
            $status = $request->get('status');

            if ($status === 'published') {
                $post->update([
                    'is_published' => true,
                    'published_at' => now(),
                    'scheduled_at' => null
                ]);
            } elseif ($status === 'draft') {
                $post->update([
                    'is_published' => false,
                    'published_at' => null,
                    'scheduled_at' => null
                ]);
            } elseif ($status === 'scheduled') {
                $post->update([
                    'is_published' => false,
                    'published_at' => null,
                    'scheduled_at' => $request->get('scheduled_at')
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating post status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post status'
            ], 500);
        }
    }

    /**
     * Bulk update posts status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id',
            'status' => 'required|in:published,draft,delete'
        ]);

        try {
            $postIds = $request->get('post_ids');
            $status = $request->get('status');

            if ($status === 'delete') {
                Post::whereIn('id', $postIds)->delete();
                $message = 'Posts deleted successfully';
            } else {
                $updateData = [
                    'is_published' => $status === 'published',
                    'published_at' => $status === 'published' ? now() : null
                ];

                Post::whereIn('id', $postIds)->update($updateData);
                $message = "Posts {$status} successfully";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update posts'
            ], 500);
        }
    }

    /**
     * Delete post
     */
    public function destroy(Post $post)
    {
        try {
            $post->delete();
            return redirect()->route('admin.posts.index')
                ->with('success', 'Post deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete post: ' . $e->getMessage());
        }
    }

    /**
     * Export posts to CSV
     */
    public function export(Request $request)
    {
        try {
            // Start with query and apply same filters as getPosts
            $query = Post::query();

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('content', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('social_circle')) {
                $socialCircleId = $request->get('social_circle');
                if (is_numeric($socialCircleId)) {
                    $query->where('social_circle_id', $socialCircleId);
                }
            }

            if ($request->filled('type')) {
                $type = $request->get('type');
                if (in_array($type, ['text', 'image', 'video', 'mixed'])) {
                    $query->where('type', $type);
                }
            }

            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'published') {
                    $query->where('is_published', true);
                } elseif ($status === 'draft') {
                    $query->where('is_published', false);
                } elseif ($status === 'scheduled') {
                    $query->where('scheduled_at', '>', now());
                }
            }

            // Add date range filtering
            if ($request->filled('date_from')) {
                $dateFrom = $request->get('date_from');
                try {
                    $query->whereDate('created_at', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_from format in export: ' . $dateFrom);
                }
            }

            if ($request->filled('date_to')) {
                $dateTo = $request->get('date_to');
                try {
                    $query->whereDate('created_at', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_to format in export: ' . $dateTo);
                }
            }

            $posts = $query->with(['user:id,name,email', 'socialCircle:id,name'])
                ->withCount(['likes', 'comments', 'reports'])
                ->get();

            $filename = 'posts_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

            $headers = array(
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            );

            $callback = function() use ($posts) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'ID', 'User Name', 'User Email', 'Social Circle', 'Content Preview',
                    'Type', 'Status', 'Likes', 'Comments', 'Reports', 'Created At'
                ]);

                foreach ($posts as $post) {
                    fputcsv($file, [
                        $post->id,
                        $post->user->name ?? 'N/A',
                        $post->user->email ?? 'N/A',
                        $post->socialCircle->name ?? 'N/A',
                        Str::limit($post->content, 100),
                        $post->type,
                        $post->is_published ? 'Published' : 'Draft',
                        $post->likes_count,
                        $post->comments_count,
                        $post->reports_count,
                        $post->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting posts: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export posts');
        }
    }
}
