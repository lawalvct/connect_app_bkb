<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupFailedStreamNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stream:cleanup-failed-notifications {--days=7 : Number of days to keep failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old failed stream notification jobs from the failed_jobs table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up failed stream notification jobs older than {$days} days...");

        try {
            $cutoffDate = Carbon::now()->subDays($days);

            // Delete old failed jobs for SendLiveStreamNotifications
            $deleted = DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->where('payload', 'like', '%SendLiveStreamNotifications%')
                ->delete();

            $this->info("✓ Deleted {$deleted} old failed notification jobs");

            // Show remaining failed jobs
            $remaining = DB::table('failed_jobs')
                ->where('payload', 'like', '%SendLiveStreamNotifications%')
                ->count();

            $this->info("✓ Remaining failed notification jobs: {$remaining}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to cleanup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
