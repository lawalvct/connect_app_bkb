<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminNotificationTypesSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'name' => 'User Registration',
                'slug' => 'user_registration',
                'description' => 'Notification for new user registrations',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'System Alert',
                'slug' => 'system_alert',
                'description' => 'System alert or warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General Info',
                'slug' => 'info',
                'description' => 'General informational notification',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($types as $type) {
            DB::table('admin_notification_types')->updateOrInsert([
                'slug' => $type['slug']
            ], $type);
        }
    }
}
