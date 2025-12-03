<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Ad;
use App\Models\Stream;
use App\Models\UserSubscription;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        // Get key metrics
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $chartData = $this->getChartData();

        return view('admin.dashboard.index', compact('stats', 'recentActivity', 'chartData'));
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();

        // User statistics
        $usersThisMonth = User::whereBetween('created_at', [$thisMonth, now()])->count();
        $usersLastMonth = User::whereBetween('created_at', [$lastMonth, $thisMonth])->count();

        // Post statistics
        $postsThisMonth = Post::whereBetween('created_at', [$thisMonth, now()])->count();
        $postsLastMonth = Post::whereBetween('created_at', [$lastMonth, $thisMonth])->count();

        // Revenue statistics
        $revenueThisMonth = UserSubscription::where('status', 'active')
            ->whereBetween('created_at', [$thisMonth, now()])
            ->sum('amount');
        $revenueLastMonth = UserSubscription::where('status', 'active')
            ->whereBetween('created_at', [$lastMonth, $thisMonth])
            ->sum('amount');

        return [
            'users' => [
                'total' => User::count(),
                'today' => User::whereDate('created_at', $today)->count(),
                'this_month' => $usersThisMonth,
                'active' => User::where('is_active', true)->count(),
                'verified' => User::where('is_verified', true)->count(),
                'growth' => $this->calculateGrowth($usersThisMonth, $usersLastMonth)
            ],
            'posts' => [
                'total' => Post::count(),
                'today' => Post::whereDate('created_at', $today)->count(),
                'this_month' => $postsThisMonth,
                'published' => Post::where('is_published', true)->count(),
                'with_media' => Post::whereHas('media')->count(),
                'growth' => $this->calculateGrowth($postsThisMonth, $postsLastMonth)
            ],
            'ads' => [
                'total' => Ad::count(),
                'active' => Ad::where('status', 'active')->count(),
                'pending' => Ad::where('status', 'pending_review')->count(),
                'rejected' => Ad::where('status', 'rejected')->count(),
                'revenue' => Ad::where('status', 'active')->sum('budget'),
                'today_revenue' => Ad::where('status', 'active')
                    ->whereDate('created_at', $today)
                    ->sum('budget')
            ],
            'subscriptions' => [
                'active' => UserSubscription::where('status', 'active')->count(),
                'expired' => UserSubscription::where('status', 'expired')->count(),
                'cancelled' => UserSubscription::where('status', 'cancelled')->count(),
                'revenue_monthly' => $revenueThisMonth,
                'revenue_total' => UserSubscription::where('status', 'active')->sum('amount'),
                'growth' => $this->calculateGrowth($revenueThisMonth, $revenueLastMonth)
            ],
            'streams' => [
                'total' => Stream::count(),
                'live' => Stream::where('status', 'live')->count(),
                'scheduled' => Stream::where('status', 'scheduled')->count(),
                'ended' => Stream::where('status', 'ended')->count(),
                'today' => Stream::whereDate('created_at', $today)->count(),
                'total_viewers' => Stream::sum('current_viewers'),
                'paid_streams' => Stream::where('is_paid', true)->count()
            ],
            'stories' => [
                'total' => \App\Models\Story::count(),
                'active' => \App\Models\Story::where('expires_at', '>', now())->count(),
                'expired' => \App\Models\Story::where('expires_at', '<=', now())->count(),
                'today' => \App\Models\Story::whereDate('created_at', $today)->count(),
                'total_views' => \App\Models\Story::sum('views_count')
            ]
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        $activities = [];

        // Recent users (last 10)
        $recentUsers = User::latest()->take(10)->get(['id', 'name', 'email', 'created_at']);
        foreach ($recentUsers as $user) {
            $activities[] = [
                'id' => 'user_' . $user->id,
                'type' => 'user_registered',
                'description' => "New user registered: {$user->name}",
                'time_ago' => $user->created_at->diffForHumans(),
                'timestamp' => $user->created_at->timestamp,
                'user_name' => $user->name,
                'user_email' => $user->email
            ];
        }

        // Recent posts (last 10) - FIX: Handle null users
        $recentPosts = Post::with('user:id,name')
            ->whereHas('user') // Only get posts where user exists
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'content', 'created_at', 'type']);

        foreach ($recentPosts as $post) {
            $userName = $post->user ? $post->user->name : 'Unknown User';
            $activities[] = [
                'id' => 'post_' . $post->id,
                'type' => 'post_created',
                'description' => "New {$post->type} by {$userName}",
                'time_ago' => $post->created_at->diffForHumans(),
                'timestamp' => $post->created_at->timestamp,
                'content' => \Str::limit($post->content, 50)
            ];
        }

        // Recent ads (last 10) - FIX: Handle null users
        $recentAds = Ad::with('user:id,name')
            ->whereHas('user') // Only get ads where user exists
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'ad_name', 'status', 'created_at']);

        foreach ($recentAds as $ad) {
            $userName = $ad->user ? $ad->user->name : 'Unknown User';
            $activities[] = [
                'id' => 'ad_' . $ad->id,
                'type' => $ad->status === 'active' ? 'ad_approved' : ($ad->status === 'rejected' ? 'ad_rejected' : 'ad_submitted'),
                'description' => "Ad '{$ad->ad_name}' by {$userName} - {$ad->status}",
                'time_ago' => $ad->created_at->diffForHumans(),
                'timestamp' => $ad->created_at->timestamp,
                'ad_name' => $ad->ad_name,
                'status' => $ad->status
            ];
        }

        // Recent subscriptions (last 10) - FIX: Handle null users
        $recentSubscriptions = UserSubscription::with('user:id,name')
            ->whereHas('user') // Only get subscriptions where user exists
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'subscription_id', 'status', 'amount', 'created_at']);

        foreach ($recentSubscriptions as $subscription) {
            $userName = $subscription->user ? $subscription->user->name : 'Unknown User';
            $activities[] = [
                'id' => 'subscription_' . $subscription->id,
                'type' => 'payment_received',
                'description' => "New subscription by {$userName} - $ {$subscription->amount}",
                'time_ago' => $subscription->created_at->diffForHumans(),
                'timestamp' => $subscription->created_at->timestamp,
                'amount' => $subscription->amount,
                'status' => $subscription->status
            ];
        }

        // Recent streams (last 10) - FIX: Handle null users
        $recentStreams = Stream::with('user:id,name')
            ->whereHas('user') // Only get streams where user exists
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'title', 'status', 'created_at']);

        foreach ($recentStreams as $stream) {
            $userName = $stream->user ? $stream->user->name : 'Unknown User';
            $activities[] = [
                'id' => 'stream_' . $stream->id,
                'type' => 'stream_started',
                'description' => "Stream '{$stream->title}' by {$userName} - {$stream->status}",
                'time_ago' => $stream->created_at->diffForHumans(),
                'timestamp' => $stream->created_at->timestamp,
                'stream_title' => $stream->title,
                'status' => $stream->status
            ];
        }

        // Sort by timestamp (most recent first)
        usort($activities, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return array_slice($activities, 0, 20); // Return top 20 activities
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData()
    {
        $last30Days = collect(range(0, 29))->map(function ($i) {
            return Carbon::now()->subDays($i)->format('Y-m-d');
        })->reverse()->values();

        // User registrations chart
        $userRegistrations = User::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        // Post creations chart
        $postCreations = Post::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        // Revenue chart
        $dailyRevenue = UserSubscription::where('status', 'active')
            ->whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->pluck('revenue', 'date');

        // Stream activities
        $streamActivities = Stream::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        // Story activities
        $storyActivities = \App\Models\Story::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        return [
            'labels' => $last30Days->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'users' => [
                'labels' => $last30Days->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'new_users' => $last30Days->map(function ($date) use ($userRegistrations) {
                    return $userRegistrations->get($date, 0);
                }),
                'active_users' => $last30Days->map(function ($date) {
                    // Active users who logged in on that date
                    return User::whereDate('last_login_at', $date)->count();
                })
            ],
            'posts' => $last30Days->map(function ($date) use ($postCreations) {
                return $postCreations->get($date, 0);
            }),
            'revenue' => [
                'labels' => $last30Days->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'data' => $last30Days->map(function ($date) use ($dailyRevenue) {
                    return $dailyRevenue->get($date, 0);
                })
            ],
            'streams' => [
                'labels' => $last30Days->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'data' => $last30Days->map(function ($date) use ($streamActivities) {
                    return $streamActivities->get($date, 0);
                })
            ],
            'stories' => [
                'labels' => $last30Days->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'data' => $last30Days->map(function ($date) use ($storyActivities) {
                    return $storyActivities->get($date, 0);
                })
            ],
            'engagement' => [
                'labels' => ['Posts', 'Stories', 'Streams', 'Ads'],
                'data' => [
                    Post::count(),
                    \App\Models\Story::count(),
                    Stream::count(),
                    Ad::count()
                ]
            ]
        ];
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get dashboard data for AJAX requests
     */
    public function getDashboardData()
    {
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $chartData = $this->getChartData();

        // Prepare stats for frontend
        $frontendStats = [
            'totalUsers' => number_format($stats['users']['total']),
            'usersGrowth' => $stats['users']['growth'] . '%',
            'activeUsers' => number_format($stats['users']['active']),
            'verifiedUsers' => number_format($stats['users']['verified']),

            'totalPosts' => number_format($stats['posts']['total']),
            'postsGrowth' => $stats['posts']['growth'] . '%',
            'publishedPosts' => number_format($stats['posts']['published']),
            'postsWithMedia' => number_format($stats['posts']['with_media']),

            'totalRevenue' => number_format($stats['subscriptions']['revenue_total'], 2),
            'revenueGrowth' => $stats['subscriptions']['growth'] . '%',
            'monthlyRevenue' => number_format($stats['subscriptions']['revenue_monthly'], 2),
            'activeSubscriptions' => number_format($stats['subscriptions']['active']),

            'liveStreams' => number_format($stats['streams']['live']),
            'totalStreams' => number_format($stats['streams']['total']),
            'scheduledStreams' => number_format($stats['streams']['scheduled']),
            'totalViewers' => number_format($stats['streams']['total_viewers']),

            'activeStories' => number_format($stats['stories']['active']),
            'totalStories' => number_format($stats['stories']['total']),
            'storyViews' => number_format($stats['stories']['total_views']),

            'activeAds' => number_format($stats['ads']['active']),
            'pendingAds' => number_format($stats['ads']['pending']),
            'adsRevenue' => number_format($stats['ads']['revenue'], 2)
        ];

        return response()->json([
            'stats' => $frontendStats,
            'recent_activity' => $recentActivity,
            'charts' => $chartData
        ]);
    }

    /**
     * Get chart data for specific period
     */
    public function getChartDataByPeriod(Request $request)
    {
        $period = $request->get('period', 30);
        $days = min(max((int) $period, 7), 90); // Limit between 7 and 90 days

        $dateRange = collect(range(0, $days - 1))->map(function ($i) {
            return Carbon::now()->subDays($i)->format('Y-m-d');
        })->reverse()->values();

        $revenue = UserSubscription::where('status', 'active')
            ->whereIn(DB::raw('DATE(created_at)'), $dateRange)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->pluck('revenue', 'date');

        return response()->json([
            'revenue' => [
                'labels' => $dateRange->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'data' => $dateRange->map(function ($date) use ($revenue) {
                    return $revenue->get($date, 0);
                })
            ]
        ]);
    }
}
