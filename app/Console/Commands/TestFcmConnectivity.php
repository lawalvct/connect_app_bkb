<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestFcmConnectivity extends Command
{
    protected $signature = 'debug:fcm-connectivity';
    protected $description = 'Test FCM API connectivity and diagnose 404 errors';

    public function handle()
    {
        $this->info('=== FCM Connectivity Test ===');
        $this->line('');

        $serverKey = config('services.firebase.server_key');
        if (!$serverKey) {
            $this->error('❌ Firebase server key not configured');
            return 1;
        }

        $this->info('Server Key: ' . substr($serverKey, 0, 20) . '...');
        $this->line('');

        // Test FCM Legacy API
        $this->info('Testing FCM Legacy API...');
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => 'dummy_token_test_12345',
                'notification' => [
                    'title' => 'Test',
                    'body' => 'Test message'
                ]
            ]);

            $this->line("HTTP Status: {$response->status()}");
            $result = $response->json();
            $this->line("Response: " . json_encode($result, JSON_PRETTY_PRINT));

            if ($response->status() === 404) {
                $this->error('❌ 404 Error - FCM Legacy API is not available');
                $this->warn('');
                $this->warn('POSSIBLE CAUSES:');
                $this->warn('1. Google has deprecated FCM Legacy API for your project');
                $this->warn('2. Legacy API is disabled in Firebase Console');
                $this->warn('3. Project settings don\'t allow Legacy API');
                $this->warn('');
                $this->info('SOLUTIONS:');
                $this->info('1. Enable Cloud Messaging API (Legacy) in Google Cloud Console');
                $this->info('2. Check Firebase Project Settings > Cloud Messaging');
                $this->info('3. Consider migrating to FCM HTTP v1 API');
            } elseif ($response->status() === 401) {
                $this->error('❌ 401 Unauthorized - Check your server key');
            } elseif ($response->status() === 400) {
                if (isset($result['error']) && $result['error'] === 'InvalidRegistration') {
                    $this->info('✅ FCM API is working! (Got expected error for dummy token)');
                } else {
                    $this->warn('⚠️ Got 400 error: ' . ($result['error'] ?? 'Unknown'));
                }
            } else {
                $this->info("Got status {$response->status()}: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('=== Next Steps ===');
        $this->line('If getting 404 errors:');
        $this->line('1. Go to https://console.cloud.google.com/apis/library');
        $this->line('2. Search for "Firebase Cloud Messaging API"');
        $this->line('3. Enable the API for your project');
        $this->line('4. Or migrate to FCM HTTP v1 API');

        return 0;
    }
}
