<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\User;
use App\Models\SocialCircle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(5)->get();
        $socialCircles = SocialCircle::limit(3)->pluck('id')->toArray();

        if (empty($socialCircles)) {
            // Create some sample social circles if none exist
            $socialCircles = [
                SocialCircle::create(['name' => 'Tech', 'color' => '#3498db', 'is_active' => true])->id,
                SocialCircle::create(['name' => 'Lifestyle', 'color' => '#e74c3c', 'is_active' => true])->id,
                SocialCircle::create(['name' => 'Business', 'color' => '#2ecc71', 'is_active' => true])->id,
            ];
        }

        foreach ($users as $user) {
            // Create sample ads for each user
            Ad::create([
                'user_id' => $user->id,
                'ad_name' => 'Summer Sale Campaign',
                'type' => 'banner',
                'description' => 'Get 50% off on all summer items. Limited time offer!',
                'call_to_action' => 'Shop Now',
                'destination_url' => 'https://example.com/summer-sale',
                'ad_placement' => [$socialCircles[0], $socialCircles[1]], // Place in first two social circles
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
                'ad_placement' => [$socialCircles[2]], // Place only in business social circle
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

            Ad::create([
                'user_id' => $user->id,
                'ad_name' => 'Holiday Special Offer',
                'type' => 'carousel',
                'description' => 'Amazing holiday deals across all categories!',
                'call_to_action' => 'Shop Deals',
                'destination_url' => 'https://example.com/holiday-deals',
                'ad_placement' => $socialCircles, // Place in all social circles
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(15),
                'budget' => 750.00,
                'daily_budget' => 37.50,
                'target_impressions' => 15000,
                'target_audience' => [
                    'age_min' => 20,
                    'age_max' => 60,
                    'gender' => 'all',
                    'locations' => ['United States', 'Canada', 'United Kingdom'],
                    'interests' => ['Shopping', 'Deals', 'Lifestyle']
                ],
                'status' => 'active',
                'admin_status' => 'approved',
                'current_impressions' => 8500,
                'clicks' => 340,
                'conversions' => 22,
                'total_spent' => 187.50,
                'created_by' => $user->id,
                'reviewed_by' => 1,
                'reviewed_at' => Carbon::now()->subDays(6),
                'activated_at' => Carbon::now()->subDays(5),
            ]);
        }
    }
}
