<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminFcmToken;
use App\Models\Admin;

class CheckAdminTokens extends Command
{
    protected $signature = 'debug:admin-tokens';
    protected $description = 'Check admin FCM tokens in database';

    public function handle()
    {
        $this->info('=== Admin FCM Tokens Debug ===');
        $this->line('');

        // Check total tokens
        $totalTokens = AdminFcmToken::count();
        $activeTokens = AdminFcmToken::where('is_active', true)->count();

        $this->info("Total Admin FCM Tokens: {$totalTokens}");
        $this->info("Active Admin FCM Tokens: {$activeTokens}");
        $this->line('');

        if ($totalTokens > 0) {
            $this->info('Token Details:');
            AdminFcmToken::with('admin')->get()->each(function ($token) {
                $adminEmail = $token->admin ? $token->admin->email : 'Unknown';
                $status = $token->is_active ? '✅ Active' : '❌ Inactive';
                $this->line("  ID: {$token->id} | Admin: {$adminEmail} | Device: {$token->device_name} | {$status}");
                $this->line("  Token: " . substr($token->fcm_token, 0, 50) . "...");
                $this->line("  Last Used: {$token->last_used_at}");
                $this->line('');
            });
        } else {
            $this->warn('❌ No admin FCM tokens found in database!');
            $this->line('');
            $this->info('This means:');
            $this->line('1. Admin subscription is not working');
            $this->line('2. Admin is not logged in during subscription');
            $this->line('3. Database connection issue');
        }

        // Check admin accounts
        $this->info('=== Admin Accounts ===');
        $adminCount = Admin::count();
        $this->info("Total Admin Accounts: {$adminCount}");

        if ($adminCount > 0) {
            Admin::all(['id', 'email', 'created_at'])->each(function ($admin) {
                $this->line("  ID: {$admin->id} | Email: {$admin->email}");
            });
        }

        // Test notification preference check
        $this->line('');
        $this->info('=== Test Notification Preferences ===');
        $testTokens = AdminFcmToken::active()->get();

        foreach ($testTokens as $token) {
            $wantsTest = $token->wantsNotification('test_notifications');
            $status = $wantsTest ? '✅ Wants' : '❌ Disabled';
            $this->line("  Token {$token->id}: {$status} test notifications");
        }

        return 0;
    }
}
