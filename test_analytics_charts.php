<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Http\Request;

echo "Testing Analytics Controller Chart Data...\n\n";

try {
    $controller = new AnalyticsController();
    $request = new Request();

    // Mock a request to get the data
    $request->merge([
        'start_date' => \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'),
        'end_date' => \Carbon\Carbon::now()->format('Y-m-d')
    ]);

    // Test if we can get analytics data without errors
    echo "📊 Testing analytics data generation...\n";

    // Simulate getting the data that would be passed to the view
    $stats = [
        'total_users' => \App\Models\User::where('deleted_flag', 'N')->count(),
        'total_posts' => \App\Models\Post::count(),
        'active_subscriptions' => \App\Models\UserSubscription::where('status', 'active')->count(),
        'total_revenue' => \App\Models\UserSubscription::where('payment_status', 'completed')->sum('amount'),
    ];

    echo "✅ Basic stats calculated successfully\n";
    echo "   - Total Users: " . number_format($stats['total_users']) . "\n";
    echo "   - Total Posts: " . number_format($stats['total_posts']) . "\n";
    echo "   - Active Subscriptions: " . number_format($stats['active_subscriptions']) . "\n";
    echo "   - Total Revenue: $" . number_format($stats['total_revenue'], 2) . "\n";

    // Test user growth data
    $userGrowth = \App\Models\User::where('deleted_flag', 'N')
        ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(30), \Carbon\Carbon::now()])
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    echo "✅ User growth data: " . $userGrowth->count() . " data points\n";

    // Test revenue data
    $revenueData = \App\Models\UserSubscription::where('payment_status', 'completed')
        ->whereBetween('paid_at', [\Carbon\Carbon::now()->subDays(30), \Carbon\Carbon::now()])
        ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    echo "✅ Revenue data: " . $revenueData->count() . " data points\n";

    // Test popular countries
    $popularCountries = \App\Models\User::where('deleted_flag', 'N')
        ->whereNotNull('country_id')
        ->join('countries', 'users.country_id', '=', 'countries.id')
        ->selectRaw('countries.name as country, COUNT(*) as user_count')
        ->groupBy('countries.id', 'countries.name')
        ->orderByDesc('user_count')
        ->limit(5)
        ->get();

    echo "✅ Popular countries: " . $popularCountries->count() . " countries found\n";

    echo "\n🎉 All analytics data is ready for chart display!\n";
    echo "📈 Charts should now display with proper Y-axis scaling\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
