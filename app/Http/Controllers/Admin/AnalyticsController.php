<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Stream;
use App\Models\UserSubscription;
use App\Models\Ad;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.permissions')->only(['index', 'users', 'content', 'revenue', 'advertising']);
    }

    public function index(Request $request)
    {
        // Get date range (default last 30 days)
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        // Dashboard Overview Stats
        $stats = [
            'total_users' => User::where('deleted_flag', 'N')->count(),
            'new_users_today' => User::where('deleted_flag', 'N')->whereDate('created_at', today())->count(),
            'total_posts' => Post::where('deleted_flag', 'N')->count(),
            'total_streams' => Stream::count(),
            'active_subscriptions' => UserSubscription::active()->count(),
            'total_revenue' => UserSubscription::where('payment_status', 'completed')->sum('amount'),
            'active_ads' => Ad::whereIn('status', ['active', 'running'])->count(),
            'total_stories' => Story::count(),
        ];

        // User Growth Chart Data (Last 30 days)
        $userGrowth = User::where('deleted_flag', 'N')
            ->whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Content Engagement Data
        $contentEngagement = [
            'total_likes' => Post::sum('likes_count'),
            'total_comments' => Post::sum('comments_count'),
            'total_shares' => Post::sum('shares_count'),
            'total_views' => Post::sum('views_count'),
            'story_views' => Story::sum('views_count'),
        ];

        // Top Performing Content
        $topPosts = Post::where('deleted_flag', 'N')
            ->with('user:id,username,profile_picture')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get(['id', 'user_id', 'content', 'views_count', 'likes_count', 'comments_count', 'created_at']);

        // Revenue Overview
        $revenueData = UserSubscription::where('payment_status', 'completed')
            ->whereBetween('paid_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Popular Countries
        $popularCountries = User::where('deleted_flag', 'N')
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as user_count')
            ->groupBy('country')
            ->orderByDesc('user_count')
            ->limit(10)
            ->get();

        return view('admin.analytics.index', compact(
            'stats',
            'userGrowth',
            'contentEngagement',
            'topPosts',
            'revenueData',
            'popularCountries',
            'startDate',
            'endDate'
        ));
    }

    public function users(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // User Demographics
        $demographics = [
            'total_users' => User::where('deleted_flag', 'N')->count(),
            'verified_users' => User::where('deleted_flag', 'N')->where('email_verified_at', '!=', null)->count(),
            'premium_users' => UserSubscription::active()->distinct('user_id')->count(),
            'business_accounts' => User::where('deleted_flag', 'N')->where('account_type', 'business')->count(),
        ];

        // User Registration Trends
        $registrationTrends = User::where('deleted_flag', 'N')
            ->whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // User Activity by Gender
        $genderStats = User::where('deleted_flag', 'N')
            ->whereNotNull('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->get();

        // Age Distribution
        $ageStats = User::where('deleted_flag', 'N')
            ->whereNotNull('date_of_birth')
            ->selectRaw('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 13 AND 17 THEN "13-17"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 24 THEN "18-24"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN "25-34"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN "35-44"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN "45-54"
                    ELSE "55+"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->get();

        // Top Countries
        $topCountries = User::where('deleted_flag', 'N')
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as user_count')
            ->groupBy('country')
            ->orderByDesc('user_count')
            ->limit(20)
            ->get();

        // Most Active Users (by posts)
        $activeUsers = User::where('deleted_flag', 'N')
            ->withCount(['posts' => function($q) {
                $q->where('deleted_flag', 'N');
            }])
            ->orderByDesc('posts_count')
            ->limit(20)
            ->get(['id', 'username', 'profile_picture', 'created_at']);

        return view('admin.analytics.users', compact(
            'demographics',
            'registrationTrends',
            'genderStats',
            'ageStats',
            'topCountries',
            'activeUsers',
            'startDate',
            'endDate'
        ));
    }

    public function content(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Content Overview
        $contentStats = [
            'total_posts' => Post::where('deleted_flag', 'N')->count(),
            'total_stories' => Story::count(),
            'total_streams' => Stream::count(),
            'posts_today' => Post::where('deleted_flag', 'N')->whereDate('created_at', today())->count(),
        ];

        // Content Creation Trends
        $contentTrends = Post::where('deleted_flag', 'N')
            ->whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as posts')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Engagement Metrics
        $engagementStats = [
            'total_likes' => Post::sum('likes_count'),
            'total_comments' => Post::sum('comments_count'),
            'total_shares' => Post::sum('shares_count'),
            'total_views' => Post::sum('views_count'),
            'avg_engagement' => Post::where('deleted_flag', 'N')->avg(DB::raw('likes_count + comments_count + shares_count')),
        ];

        // Top Posts by Engagement
        $topPosts = Post::where('deleted_flag', 'N')
            ->with('user:id,username,profile_picture')
            ->selectRaw('*, (likes_count + comments_count + shares_count) as total_engagement')
            ->orderByDesc('total_engagement')
            ->limit(20)
            ->get();

        // Content Type Distribution
        $contentTypes = Post::where('deleted_flag', 'N')
            ->selectRaw('
                CASE 
                    WHEN file_url IS NOT NULL AND file_url LIKE "%.mp4%" THEN "Video"
                    WHEN file_url IS NOT NULL THEN "Image"
                    ELSE "Text"
                END as content_type,
                COUNT(*) as count
            ')
            ->groupBy('content_type')
            ->get();

        // Story Analytics
        $storyStats = [
            'total_stories' => Story::count(),
            'story_views' => Story::sum('views_count'),
            'avg_story_views' => Story::avg('views_count'),
            'active_stories' => Story::where('expires_at', '>', now())->count(),
        ];

        return view('admin.analytics.content', compact(
            'contentStats',
            'contentTrends',
            'engagementStats',
            'topPosts',
            'contentTypes',
            'storyStats',
            'startDate',
            'endDate'
        ));
    }

    public function revenue(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Revenue Overview
        $revenueStats = [
            'total_revenue' => UserSubscription::where('payment_status', 'completed')->sum('amount'),
            'monthly_revenue' => UserSubscription::where('payment_status', 'completed')
                ->whereMonth('paid_at', Carbon::now()->month)
                ->sum('amount'),
            'active_subscriptions' => UserSubscription::active()->count(),
            'avg_subscription_value' => UserSubscription::where('payment_status', 'completed')->avg('amount'),
        ];

        // Daily Revenue Trends
        $revenueTrends = UserSubscription::where('payment_status', 'completed')
            ->whereBetween('paid_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Subscription Status Distribution
        $subscriptionStatus = UserSubscription::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Payment Methods
        $paymentMethods = UserSubscription::where('payment_status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get();

        // Currency Distribution
        $currencyStats = UserSubscription::where('payment_status', 'completed')
            ->selectRaw('currency, COUNT(*) as transactions, SUM(amount) as total_amount')
            ->groupBy('currency')
            ->get();

        // Top Revenue Generating Users
        $topUsers = UserSubscription::where('payment_status', 'completed')
            ->with('user:id,username,profile_picture')
            ->selectRaw('user_id, SUM(amount) as total_spent, COUNT(*) as subscription_count')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();

        // Subscription Renewal Rates
        $renewalStats = [
            'auto_renew_enabled' => UserSubscription::active()->where('auto_renew', true)->count(),
            'manual_renewals' => UserSubscription::active()->where('auto_renew', false)->count(),
            'cancelled_subscriptions' => UserSubscription::whereNotNull('cancelled_at')->count(),
        ];

        return view('admin.analytics.revenue', compact(
            'revenueStats',
            'revenueTrends',
            'subscriptionStatus',
            'paymentMethods',
            'currencyStats',
            'topUsers',
            'renewalStats',
            'startDate',
            'endDate'
        ));
    }

    public function advertising(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Ad Overview Stats
        $adStats = [
            'total_ads' => Ad::count(),
            'active_ads' => Ad::whereIn('status', ['active', 'running'])->count(),
            'pending_review' => Ad::where('admin_status', 'pending')->count(),
            'total_ad_spend' => Ad::sum('total_spent'),
            'total_impressions' => Ad::sum('current_impressions'),
            'total_clicks' => Ad::sum('clicks'),
            'avg_ctr' => Ad::where('current_impressions', '>', 0)->avg(DB::raw('clicks / current_impressions * 100')),
        ];

        // Ad Performance Trends
        $adTrends = Ad::whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as ads_created, SUM(total_spent) as daily_spend')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ad Status Distribution
        $adStatusStats = Ad::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Top Performing Ads
        $topAds = Ad::with('user:id,username')
            ->whereIn('status', ['active', 'running', 'completed'])
            ->selectRaw('*, (clicks / NULLIF(current_impressions, 0) * 100) as ctr')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        // Ad Types Distribution
        $adTypes = Ad::selectRaw('type, COUNT(*) as count, SUM(total_spent) as total_spend')
            ->groupBy('type')
            ->get();

        // Budget Analysis
        $budgetStats = [
            'total_budget_allocated' => Ad::sum('budget'),
            'total_spent' => Ad::sum('total_spent'),
            'avg_daily_budget' => Ad::avg('daily_budget'),
            'budget_utilization' => Ad::where('budget', '>', 0)->avg(DB::raw('total_spent / budget * 100')),
        ];

        // Revenue by Country (Ad Targeting)
        $countryStats = Ad::whereNotNull('target_countries')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(target_countries, "$[0]")) as country, COUNT(*) as ad_count, SUM(total_spent) as spend')
            ->groupBy('country')
            ->orderByDesc('spend')
            ->limit(10)
            ->get();

        return view('admin.analytics.advertising', compact(
            'adStats',
            'adTrends',
            'adStatusStats',
            'topAds',
            'adTypes',
            'budgetStats',
            'countryStats',
            'startDate',
            'endDate'
        ));
    }

    public function streaming(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Streaming Overview
        $streamStats = [
            'total_streams' => Stream::count(),
            'live_streams' => Stream::where('status', 'live')->count(),
            'completed_streams' => Stream::where('status', 'ended')->count(),
            'total_viewers' => Stream::sum('max_viewers'),
            'avg_viewers_per_stream' => Stream::avg('max_viewers'),
            'total_streaming_revenue' => Stream::whereNotNull('pricing')->sum('pricing'),
        ];

        // Streaming Trends
        $streamTrends = Stream::whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as streams, SUM(max_viewers) as total_viewers')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top Streamers
        $topStreamers = Stream::with('user:id,username,profile_picture')
            ->selectRaw('user_id, COUNT(*) as stream_count, SUM(max_viewers) as total_viewers, AVG(max_viewers) as avg_viewers')
            ->groupBy('user_id')
            ->orderByDesc('total_viewers')
            ->limit(20)
            ->get();

        // Stream Duration Analysis
        $durationStats = Stream::where('status', 'ended')
            ->whereNotNull('ended_at')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as avg_duration,
                MAX(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as max_duration,
                MIN(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as min_duration
            ')
            ->first();

        // Stream Status Distribution
        $streamStatusStats = Stream::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return view('admin.analytics.streaming', compact(
            'streamStats',
            'streamTrends',
            'topStreamers',
            'durationStats',
            'streamStatusStats',
            'startDate',
            'endDate'
        ));
    }

    public function exportData(Request $request)
    {
        $type = $request->get('type', 'overview');
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        switch ($type) {
            case 'users':
                return $this->exportUsersData($startDate, $endDate);
            case 'content':
                return $this->exportContentData($startDate, $endDate);
            case 'revenue':
                return $this->exportRevenueData($startDate, $endDate);
            case 'advertising':
                return $this->exportAdvertisingData($startDate, $endDate);
            default:
                return $this->exportOverviewData($startDate, $endDate);
        }
    }

    private function exportOverviewData($startDate, $endDate)
    {
        // Implementation for CSV export of overview data
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics_overview_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Metric', 'Value']);
            
            fputcsv($file, ['Total Users', User::where('deleted_flag', 'N')->count()]);
            fputcsv($file, ['Total Posts', Post::where('deleted_flag', 'N')->count()]);
            fputcsv($file, ['Total Revenue', UserSubscription::where('payment_status', 'completed')->sum('amount')]);
            fputcsv($file, ['Active Subscriptions', UserSubscription::active()->count()]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Additional private methods for other export types would go here...
}
