<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PushNotificationLog;

class CheckPushLogs extends Command
{
    protected $signature = 'debug:push-logs {--limit=10}';
    protected $description = 'Check recent push notification logs';

    public function handle()
    {
        $limit = $this->option('limit');

        $this->info('=== Recent Push Notification Logs ===');
        $this->line('');

        $logs = PushNotificationLog::latest()
            ->limit($limit)
            ->get();

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                $status = $log->status === 'sent' ? '✅' : '❌';
                $userInfo = $log->user_id ? "User ID: {$log->user_id}" : 'Admin Notification';

                $this->line("{$status} {$log->title}");
                $this->line("  {$userInfo} | Status: {$log->status}");
                $this->line("  Sent: {$log->sent_at}");

                if ($log->error_message) {
                    $this->line("  Error: {$log->error_message}");
                }

                // Show admin_id from data if available
                if (isset($log->data['admin_id'])) {
                    $this->line("  Admin ID: {$log->data['admin_id']}");
                }

                $this->line('');
            }
        } else {
            $this->warn('No push notification logs found.');
        }

        // Summary
        $total = PushNotificationLog::count();
        $sent = PushNotificationLog::where('status', 'sent')->count();
        $failed = PushNotificationLog::where('status', 'failed')->count();

        $this->info("=== Summary ===");
        $this->line("Total Logs: {$total}");
        $this->line("Sent: {$sent}");
        $this->line("Failed: {$failed}");
    }
}
