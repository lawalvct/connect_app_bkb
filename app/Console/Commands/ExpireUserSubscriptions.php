<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
use App\Helpers\UserSubscriptionHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExpireUserSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire {--dry-run : Run without making changes} {--notify : Send expiration notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire user subscriptions that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $notify = $this->option('notify');

        $this->info('🔍 Checking for expired subscriptions...');

        try {
            // Get subscriptions expiring today or already expired
            $expiredSubscriptions = UserSubscription::where('expires_at', '<=', now())
                ->where('status', 'active')
                ->where('deleted_flag', 'N')
                ->with(['user', 'subscription'])
                ->get();

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('✅ No expired subscriptions found.');
                return 0;
            }

            $this->info("📋 Found {$expiredSubscriptions->count()} expired subscriptions");

            if ($dryRun) {
                $this->warn('🧪 DRY RUN MODE - No changes will be made');
                $this->displayExpiredSubscriptions($expiredSubscriptions);
                return 0;
            }

            // Process expired subscriptions
            $expiredCount = 0;
            $notificationsSent = 0;

            DB::beginTransaction();

            foreach ($expiredSubscriptions as $subscription) {
                // Mark subscription as expired
                $subscription->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);

                $expiredCount++;

                $this->line("⏰ Expired: {$subscription->user->name} - {$subscription->subscription->name}");

                // Send notification if requested
                if ($notify && $subscription->user->email) {
                    try {
                        $this->sendExpirationNotification($subscription);
                        $notificationsSent++;
                        $this->line("📧 Notification sent to {$subscription->user->email}");
                    } catch (\Exception $e) {
                        $this->error("❌ Failed to send notification to {$subscription->user->email}: {$e->getMessage()}");
                    }
                }
            }

            DB::commit();

            // Check for subscriptions expiring soon (next 7 days)
            if ($notify) {
                $this->checkExpiringSubscriptions();
            }

            $this->info("✅ Successfully expired {$expiredCount} subscriptions");

            if ($notify) {
                $this->info("📧 Sent {$notificationsSent} expiration notifications");
            }

            // Log the operation
            Log::info('Subscription expiration check completed', [
                'expired_count' => $expiredCount,
                'notifications_sent' => $notificationsSent,
                'execution_time' => now()
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('❌ Error processing subscription expirations: ' . $e->getMessage());

            Log::error('Subscription expiration check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Display expired subscriptions in dry run mode
     */
    private function displayExpiredSubscriptions($subscriptions)
    {
        $headers = ['User ID', 'User Name', 'Email', 'Subscription', 'Expired Date', 'Days Overdue'];
        $rows = [];

        foreach ($subscriptions as $subscription) {
            $daysOverdue = now()->diffInDays($subscription->expires_at);

            $rows[] = [
                $subscription->user_id,
                $subscription->user->name ?? 'N/A',
                $subscription->user->email ?? 'N/A',
                $subscription->subscription->name ?? 'Unknown',
                $subscription->expires_at->format('Y-m-d H:i:s'),
                $daysOverdue . ' days'
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Check for subscriptions expiring in the next 7 days
     */
    private function checkExpiringSubscriptions()
    {
        $expiringSoon = UserSubscription::whereBetween('expires_at', [
                now()->addDay(),
                now()->addDays(7)
            ])
            ->where('status', 'active')
            ->where('deleted_flag', 'N')
            ->with(['user', 'subscription'])
            ->get();

        if ($expiringSoon->isNotEmpty()) {
            $this->info("⚠️  Found {$expiringSoon->count()} subscriptions expiring in the next 7 days");

            foreach ($expiringSoon as $subscription) {
                $daysUntilExpiry = now()->diffInDays($subscription->expires_at);
                $this->line("⏳ Expiring in {$daysUntilExpiry} days: {$subscription->user->name} - {$subscription->subscription->name}");

                // Send reminder notification
                try {
                    $this->sendExpirationReminder($subscription, $daysUntilExpiry);
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send reminder to {$subscription->user->email}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Send expiration notification email
     */
    private function sendExpirationNotification($subscription)
    {
        // For now, we'll just log the notification
        // You can implement actual email sending here
        Log::info('Subscription expired notification', [
            'user_id' => $subscription->user_id,
            'user_email' => $subscription->user->email,
            'subscription_name' => $subscription->subscription->name,
            'expired_at' => $subscription->expires_at
        ]);

        // Uncomment and implement when you have email templates ready
        /*
        Mail::send('emails.subscription-expired', [
            'user' => $subscription->user,
            'subscription' => $subscription
        ], function ($message) use ($subscription) {
            $message->to($subscription->user->email)
                    ->subject('Your subscription has expired - ConnectApp');
        });
        */
    }

    /**
     * Send expiration reminder notification
     */
    private function sendExpirationReminder($subscription, $daysUntilExpiry)
    {
        // For now, we'll just log the reminder
        Log::info('Subscription expiring reminder', [
            'user_id' => $subscription->user_id,
            'user_email' => $subscription->user->email,
            'subscription_name' => $subscription->subscription->name,
            'expires_at' => $subscription->expires_at,
            'days_until_expiry' => $daysUntilExpiry
        ]);

        // Uncomment and implement when you have email templates ready
        /*
        Mail::send('emails.subscription-expiring', [
            'user' => $subscription->user,
            'subscription' => $subscription,
            'days_until_expiry' => $daysUntilExpiry
        ], function ($message) use ($subscription) {
            $message->to($subscription->user->email)
                    ->subject("Your subscription expires in {$daysUntilExpiry} days - ConnectApp");
        });
        */
    }
}
