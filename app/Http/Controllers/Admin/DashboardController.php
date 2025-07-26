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

        return [
            'users' => [
                'total' => User::count(),
                'today' => User::whereDate('created_at', $today)->count(),
                'growth' => $this->calculateGrowth(
                    User::whereDate('created_at', $today)->count(),
                    User::whereDate('created_at', $yesterday)->count()
                )
            ],
            'posts' => [
                'total' => Post::count(),
                'today' => Post::whereDate('created_at', $today)->count(),
                'growth' => $this->calculateGrowth(
                    Post::whereDate('created_at', $today)->count(),
                    Post::whereDate('created_at', $yesterday)->count()
                )
            ],
            'ads' => [
                'total' => Ad::count(),
                'active' => Ad::where('status', 'active')->count(),
                'pending' => Ad::where('status', 'pending_review')->count(),
                'revenue' => Ad::where('status', 'active')->sum('budget')
            ],
            'subscriptions' => [
                'active' => UserSubscription::where('status', 'active')->count(),
                'revenue_monthly' => UserSubscription::where('status', 'active')
                    ->whereBetween('created_at', [$thisMonth, now()])
                    ->sum('amount'),
                'revenue_total' => UserSubscription::where('status', 'active')->sum('amount')
            ],
            'streams' => [
                'total' => Stream::count(),
                'live' => Stream::where('status', 'live')->count(),
                'scheduled' => Stream::where('status', 'scheduled')->count()
            ]
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        return [
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_posts' => Post::with('user:id,name')->latest()->take(5)->get(['id', 'user_id', 'content', 'created_at']),
            'recent_ads' => Ad::with('user:id,name')->latest()->take(5)->get(['id', 'user_id', 'ad_name', 'status', 'created_at']),
            'recent_subscriptions' => UserSubscription::with('user:id,name', 'subscription:id,name')
                ->latest()->take(5)->get(['id', 'user_id', 'subscription_id', 'status', 'amount', 'created_at'])
        ];
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData()
    {
        $last30Days = collect(range(0, 29))->map(function ($i) {
            return Carbon::now()->subDays($i)->format('Y-m-d');
        })->reverse()->values();

        $userRegistrations = User::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        $postCreations = Post::whereIn(DB::raw('DATE(created_at)'), $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date');

        return [
            'labels' => $last30Days->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'users' => $last30Days->map(function ($date) use ($userRegistrations) {
                return $userRegistrations->get($date, 0);
            }),
            'posts' => $last30Days->map(function ($date) use ($postCreations) {
                return $postCreations->get($date, 0);
            })
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

        // Format recent activity for frontend
        $formattedActivity = [];

        // Recent users
        foreach ($stats['recent_users'] ?? [] as $user) {
            $formattedActivity[] = [
                'id' => 'user_' . $user->id,
                'type' => 'user_registered',
                'description' => "New user registered: {$user->name}",
                'time_ago' => $user->created_at->diffForHumans()
            ];
        }

        // Recent posts
        foreach ($stats['recent_posts'] ?? [] as $post) {
            $formattedActivity[] = [
                'id' => 'post_' . $post->id,
                'type' => 'post_created',
                'description' => "New post by {$post->user->name}",
                'time_ago' => $post->created_at->diffForHumans()
            ];
        }

        // Sort by time
        usort($formattedActivity, function ($a, $b) {
            return strtotime($b['time_ago']) - strtotime($a['time_ago']);
        });

        // Prepare stats for frontend
        $frontendStats = [
            'totalUsers' => $stats['users']['total'],
            'usersGrowth' => $stats['users']['growth'] . '%',
            'activePosts' => $stats['posts']['total'],
            'postsGrowth' => $stats['posts']['growth'] . '%',
            'totalRevenue' => number_format($stats['subscriptions']['revenue_total'], 2),
            'revenueGrowth' => '12.5%', // You can calculate this based on your logic
            'activeStreams' => $stats['streams']['live'],
            'streamsChange' => $stats['streams']['total'] - $stats['streams']['live']
        ];

        return response()->json([
            'stats' => $frontendStats,
            'recent_activity' => array_slice($formattedActivity, 0, 10),
            'charts' => $this->getChartData()
        ]);
    }
}
