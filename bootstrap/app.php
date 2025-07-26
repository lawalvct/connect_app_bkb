<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SwipeRateLimit;

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
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'swipe.limit' => SwipeRateLimit::class,
        ]);

        // API middleware group configuration
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
          // Disable CSRF for API routes
          $middleware->validateCsrfTokens(except: [
            'api/*',
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
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Reset daily swipes at midnight
        $schedule->command('swipes:reset-daily')
                 ->dailyAt('00:01')
                 ->timezone('UTC');

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
