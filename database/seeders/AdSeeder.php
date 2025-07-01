<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(5)->get();

        foreach ($users as $user) {
            // Create sample ads for each user
            Ad::create([
                'user_id' => $user->id,
                'ad_name' => 'Summer Sale Campaign',
                'type' => 'banner',
                'description' => 'Get 50% off on all summer items. Limited time offer!',
                'call_to_action' => 'Shop Now',
                'destination_url' => 'https://example.com/summer-sale',
                'start_date' => Carbon::now()->addDays(1),
                'end_date' => Carbon::now()->addDays(30),
                'budget' => 500.00,
                'daily_budget' => 16.67,
                'target_impressions' => 10000,
                'target_audience' => [
                    'age_min' => 18,
                    'age_max' => 45,
                    'gender' => 'all',
                    'locations' => ['United States', 'Canada'],
                    'interests' => ['Fashion', 'Shopping']
                ],
                'status' => 'active',
                'admin_status' => 'approved',
                'current_impressions' => 2500,
                'clicks' => 125,
                'conversions' => 8,
                'total_spent' => 87.50,
                'created_by' => $user->id,
                'reviewed_by' => 1, // Assuming admin user ID is 1
                'reviewed_at' => Carbon::now()->subDays(2),
                'activated_at' => Carbon::now()->subDays(1),
            ]);

            Ad::create([
                'user_id' => $user->id,
                'ad_name' => 'New Product Launch',
                'type' => 'video',
                'description' => 'Introducing our revolutionary new product. Be the first to try it!',
                'call_to_action' => 'Learn More',
                'destination_url' => 'https://example.com/new-product',
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addDays(25),
                'budget' => 1000.00,
                'daily_budget' => 50.00,
                'target_impressions' => 25000,
                'target_audience' => [
                    'age_min' => 25,
                    'age_max' => 55,
                    'gender' => 'all',
                    'locations' => ['United States'],
                    'interests' => ['Technology', 'Innovation']
                ],
                'status' => 'pending_review',
                'admin_status' => 'pending',
                'created_by' => $user->id,
            ]);
        }
    }
}
