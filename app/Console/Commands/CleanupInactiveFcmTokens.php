<?php

namespace App\Console\Commands;

use App\Models\UserFcmToken;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupInactiveFcmTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:cleanup-inactive
                            {--days=90 : Number of days of inactivity before cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up FCM tokens that have not been used for specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up FCM tokens inactive since: {$cutoffDate->toDateTimeString()}");

        // Find tokens that are marked active but haven't been used
        $inactiveTokens = UserFcmToken::where('is_active', true)
            ->where(function($q) use ($cutoffDate) {
                $q->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<', $cutoffDate);
            })
            ->get();

        $count = $inactiveTokens->count();

        if ($count === 0) {
            $this->info('No inactive tokens found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} inactive tokens. Deactivating...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($inactiveTokens as $token) {
            $token->deactivate();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        Log::info("Cleaned up {$count} inactive FCM tokens", [
            'cutoff_date' => $cutoffDate,
            'days' => $days
        ]);

        $this->info("Successfully deactivated {$count} inactive FCM tokens.");

        return Command::SUCCESS;
    }
}
