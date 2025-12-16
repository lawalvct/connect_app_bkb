<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SwipeRateLimit;
use App\Http\Middleware\UpdateLastActivity;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api/v1.php'));

            Route::middleware('api')
                ->prefix('api/v2')
                ->group(base_path('routes/api/v2.php'));

            // Load admin routes
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            // Load pusher test routes
            Route::middleware('web')
                ->group(base_path('routes/pusher-test.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'swipe.limit' => SwipeRateLimit::class,
            'admin.permissions' => \App\Http\Middleware\AdminPermissions::class,
            'check.subscriptions' => \App\Http\Middleware\CheckExpiredSubscriptions::class,
        ]);

        // API middleware group configuration
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CheckExpiredSubscriptions::class,
            \App\Http\Middleware\UpdateLastActivity::class,
        ]);
          // Disable CSRF for API routes
          $middleware->validateCsrfTokens(except: [
            'api/*',
            'admin/api/*', // Exclude admin API routes from CSRF validation
        ]);
        $middleware->append(\App\Http\Middleware\HandleCors::class);

        // Configure authentication redirects
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin/*')) {
                return route('admin.auth.login');
            }
            return route('login');
        });
    })

    ->withCommands([
        \App\Console\Commands\ResetDailySwipes::class,
        \App\Console\Commands\ExpireUserSubscriptions::class,
        \App\Console\Commands\CleanupInactiveFcmTokens::class,
        \App\Console\Commands\SendAdReminders::class,
        \App\Console\Commands\CleanupExpiredStories::class,
        \App\Console\Commands\PublishScheduledPosts::class,
        \App\Console\Commands\CleanupFailedStreamNotifications::class,
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // ============================================
        // EVERY MINUTE TASKS
        // ============================================

        // Publish scheduled posts every minute
        $schedule->command('posts:publish-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ============================================
        // HOURLY TASKS
        // ============================================

        // Clean up expired stories every hour
        $schedule->command('stories:cleanup')
                 ->hourly()
                 ->withoutOverlapping();

        // ============================================
        // DAILY TASKS
        // ============================================

        // Reset daily swipes at midnight UTC
        $schedule->command('swipes:reset-daily')
                 ->dailyAt('00:01')
                 ->timezone('UTC')
                 ->withoutOverlapping();

        // Check and expire user subscriptions daily at 6 AM
        $schedule->command('subscriptions:expire --notify')
                 ->dailyAt('06:00')
                 ->timezone('UTC')
                 ->withoutOverlapping();

        // Send ad expiry reminders daily at 9 AM
        $schedule->command('ads:send-reminders')
                 ->dailyAt('09:00')
                 ->timezone('UTC')
                 ->withoutOverlapping();

        // ============================================
        // WEEKLY TASKS
        // ============================================

        // Cleanup inactive FCM tokens weekly on Sunday at 2 AM
        $schedule->command('fcm:cleanup-inactive')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->timezone('UTC')
                 ->withoutOverlapping();

        // Cleanup old failed stream notification jobs weekly
        $schedule->command('stream:cleanup-failed-notifications')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->timezone('UTC')
                 ->withoutOverlapping();

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
