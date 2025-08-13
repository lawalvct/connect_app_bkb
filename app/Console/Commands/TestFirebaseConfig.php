<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestFirebaseConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Firebase configuration values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Firebase Configuration...');
        $this->line('');

        $config = [
            'api_key' => config('services.firebase.api_key'),
            'auth_domain' => config('services.firebase.auth_domain'),
            'project_id' => config('services.firebase.project_id'),
            'storage_bucket' => config('services.firebase.storage_bucket'),
            'messaging_sender_id' => config('services.firebase.messaging_sender_id'),
            'app_id' => config('services.firebase.app_id'),
            'vapid_key' => config('services.firebase.vapid_key'),
            'server_key' => config('services.firebase.server_key'),
        ];

        $hasErrors = false;
        $hasPlaceholders = false;

        // Define placeholder patterns to detect
        $placeholderPatterns = [
            'your_actual_api_key_from_firebase_console',
            'your_messaging_sender_id',
            'your_app_id_from_firebase_console',
            'your-value-here',
            'example_key_here',
            'your_firebase_server_key_here'
        ];

        foreach ($config as $key => $value) {
            $isPlaceholder = false;
            $status = $value ? '✅' : '❌';
            $displayValue = $value ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : 'NOT SET';

            // Check if value is a placeholder
            if ($value) {
                foreach ($placeholderPatterns as $pattern) {
                    if (strpos($value, $pattern) !== false) {
                        $isPlaceholder = true;
                        $hasPlaceholders = true;
                        $status = '⚠️';
                        break;
                    }
                }
            }

            if (!$value) {
                $hasErrors = true;
                $this->error("  {$status} {$key}: {$displayValue}");
            } elseif ($isPlaceholder) {
                $hasErrors = true;
                $this->warn("  {$status} {$key}: {$displayValue} (PLACEHOLDER - NEEDS REAL VALUE)");
            } else {
                $this->info("  {$status} {$key}: {$displayValue}");
            }
        }

        $this->line('');

        if ($hasErrors) {
            if ($hasPlaceholders) {
                $this->error('❌ Firebase configuration has PLACEHOLDER values!');
                $this->line('');
                $this->warn('🔧 SOLUTION: You need to replace placeholder values with real Firebase configuration.');
                $this->line('');
                $this->info('Visit the Firebase setup helper: ' . url('/firebase-setup'));
                $this->line('');
                $this->warn('Steps:');
                $this->line('1. Go to https://console.firebase.google.com/project/connect-app-fbaca');
                $this->line('2. Add a web app or click existing web app');
                $this->line('3. Copy the REAL configuration values');
                $this->line('4. Replace placeholder values in your .env file');
                $this->line('5. Run: php artisan config:clear');
            } else {
                $this->error('❌ Firebase configuration has missing values!');
                $this->line('');
                $this->warn('Please add the missing values to your .env file:');
                $this->line('');
                foreach ($config as $key => $value) {
                    if (!$value) {
                        $envKey = 'FIREBASE_' . strtoupper($key);
                        $this->line("  {$envKey}=your-value-here");
                    }
                }
            }
        } else {
            $this->info('✅ All Firebase configuration values are set with real values!');
            $this->info('🎉 Your Firebase setup should work correctly now.');
        }

        $this->line('');
        $this->info('You can also test the frontend by visiting:');
        $this->line('  • ' . url('/firebase-test'));
        $this->line('  • ' . url('/admin/notifications/subscription'));

        return $hasErrors ? 1 : 0;
    }
}
