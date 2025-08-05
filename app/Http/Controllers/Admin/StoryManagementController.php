<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StoryManagementController extends Controller
{
    public function index(Request $request)
    {
        $stories = Story::with([
            'user:id,name,username,email,profile,profile_url',
            'views.user:id,name',
            'replies.user:id,name'
        ])
        ->withCount(['views', 'replies'])
        ->latest()
        ->paginate(20);

        $stats = [
            'total_stories' => Story::count(),
            'active_stories' => Story::active()->count(),
            'expired_stories' => Story::expired()->count(),
            'total_views' => Story::sum('views_count'),
            'today_stories' => Story::whereDate('created_at', today())->count(),
            'this_week_stories' => Story::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'stories' => $stories,
                'stats' => $stats
            ]);
        }

        return view('admin.stories.index', compact('stories', 'stats'));
    }

    public function show(Story $story)
    {
        $story->load([
            'user:id,name,username,email,profile,profile_url',
            'views.user:id,name,username,profile,profile_url',
            'replies.user:id,name,username,profile,profile_url'
        ]);

        return view('admin.stories.show', compact('story'));
    }

    public function destroy(Story $story)
    {
        try {
            // Delete associated file if exists
            if ($story->file_url) {
                // Handle file deletion from storage
                $filePath = str_replace('/storage/', 'public/', $story->file_url);
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            }

            $story->delete();

            return response()->json([
                'success' => true,
                'message' => 'Story deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete story: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStories(Request $request): JsonResponse
    {
        $query = Story::with([
            'user:id,name,username,email,profile,profile_url'
        ])->withCount(['views', 'replies']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caption', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filter by status (active/expired)
        if ($request->has('status') && !empty($request->status)) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            }
        }

        // Filter by privacy
        if ($request->has('privacy') && !empty($request->privacy)) {
            $query->where('privacy', $request->privacy);
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by user
        if ($request->has('user_id') && !empty($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'views_count', 'expires_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest();
        }

        $stories = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'stories' => $stories,
            'total' => $stories->total(),
            'current_page' => $stories->currentPage(),
            'last_page' => $stories->lastPage(),
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $timeframe = $request->get('timeframe', 'week'); // day, week, month, year

        $stats = [
            'total_stories' => Story::count(),
            'active_stories' => Story::active()->count(),
            'expired_stories' => Story::expired()->count(),
            'total_views' => Story::sum('views_count'),
            'average_views' => round(Story::avg('views_count'), 1),
        ];

        // Time-based stats
        switch ($timeframe) {
            case 'day':
                $stats['period_stories'] = Story::whereDate('created_at', today())->count();
                $stats['period_views'] = Story::whereDate('created_at', today())->sum('views_count');
                break;
            case 'week':
                $stats['period_stories'] = Story::whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->count();
                $stats['period_views'] = Story::whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->sum('views_count');
                break;
            case 'month':
                $stats['period_stories'] = Story::whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year)
                                                ->count();
                $stats['period_views'] = Story::whereMonth('created_at', now()->month)
                                              ->whereYear('created_at', now()->year)
                                              ->sum('views_count');
                break;
        }

        // Type breakdown
        $stats['type_breakdown'] = Story::select('type', DB::raw('count(*) as count'))
                                       ->groupBy('type')
                                       ->get()
                                       ->pluck('count', 'type')
                                       ->toArray();

        // Privacy breakdown
        $stats['privacy_breakdown'] = Story::select('privacy', DB::raw('count(*) as count'))
                                          ->groupBy('privacy')
                                          ->get()
                                          ->pluck('count', 'privacy')
                                          ->toArray();

        // Top performers
        $stats['top_stories'] = Story::with('user:id,name,username')
                                   ->orderBy('views_count', 'desc')
                                   ->take(5)
                                   ->get(['id', 'user_id', 'type', 'caption', 'views_count', 'created_at']);

        return response()->json($stats);
    }

    public function export(Request $request)
    {
        $stories = Story::with('user:id,name,username,email')
                      ->withCount(['views', 'replies'])
                      ->get();

        $filename = 'stories_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($stories) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID', 'User', 'Username', 'Email', 'Type', 'Caption', 'Content',
                'Privacy', 'Views', 'Replies', 'Status', 'Created At', 'Expires At'
            ]);

            foreach ($stories as $story) {
                fputcsv($file, [
                    $story->id,
                    $story->user->name ?? 'N/A',
                    $story->user->username ?? 'N/A',
                    $story->user->email ?? 'N/A',
                    ucfirst($story->type),
                    $story->caption ?? 'N/A',
                    Str::limit($story->content ?? 'N/A', 50),
                    ucfirst(str_replace('_', ' ', $story->privacy)),
                    $story->views_count,
                    $story->replies_count,
                    $story->is_expired ? 'Expired' : 'Active',
                    $story->created_at->format('Y-m-d H:i:s'),
                    $story->expires_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'story_ids' => 'required|array',
            'story_ids.*' => 'exists:stories,id'
        ]);

        try {
            $stories = Story::whereIn('id', $request->story_ids)->get();

            foreach ($stories as $story) {
                // Delete associated files
                if ($story->file_url) {
                    $filePath = str_replace('/storage/', 'public/', $story->file_url);
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                    }
                }
            }

            Story::whereIn('id', $request->story_ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($request->story_ids) . ' stories deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanupExpired(): JsonResponse
    {
        try {
            Log::info('Starting cleanup of expired stories');

            $expiredStories = Story::expired()->get();
            $deletedCount = 0;
            $deletedFiles = 0;

            Log::info('Found ' . $expiredStories->count() . ' expired stories to cleanup');

            foreach ($expiredStories as $story) {
                // Delete associated files
                if ($story->file_url) {
                    $filePath = str_replace('/storage/', 'public/', $story->file_url);
                    if (Storage::exists($filePath)) {
                        try {
                            Storage::delete($filePath);
                            $deletedFiles++;
                            Log::info('Deleted file: ' . $filePath);
                        } catch (\Exception $e) {
                            Log::warning('Failed to delete file: ' . $filePath . ' - ' . $e->getMessage());
                        }
                    }
                }
            }

            // Delete expired stories
            $deletedCount = Story::expired()->delete();

            Log::info("Cleanup completed: {$deletedCount} stories and {$deletedFiles} files deleted");

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} expired stories and {$deletedFiles} associated files cleaned up successfully",
                'deleted_stories' => $deletedCount,
                'deleted_files' => $deletedFiles
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired stories: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup expired stories: ' . $e->getMessage()
            ], 500);
        }
    }
}
