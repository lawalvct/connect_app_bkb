<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
      //  ProcessAdMetrics::class,
       // SendAdReminders::class,
        \App\Console\Commands\TestAdSystem::class, // Add this line
        \App\Console\Commands\ExpireUserSubscriptions::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send ad expiry reminders daily at 9 AM
        $schedule->command('ads:send-reminders')->dailyAt('09:00');

        // Check for subscription expiration daily at 6 AM
        $schedule->command('subscriptions:expire --notify')->dailyAt('06:00');

        // You can add more scheduled tasks here
        // $schedule->command('ads:update-performance')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
