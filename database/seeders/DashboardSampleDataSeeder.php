<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Stream;
use App\Models\UserSubscription;
use App\Models\Ad;
use App\Models\Story;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DashboardSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding sample data for dashboard...');

        // Create sample users over the last 30 days
        $this->seedUsers();

        // Create sample posts
        $this->seedPosts();

        // Create sample streams
        $this->seedStreams();

        // Create sample subscriptions
        $this->seedSubscriptions();

        // Create sample ads
        $this->seedAds();

        // Create sample stories
        $this->seedStories();

        $this->command->info('Sample data seeded successfully!');
    }

    private function seedUsers()
    {
        $this->command->info('Creating sample users...');

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            $usersToday = rand(1, 3); // Reduced number

            for ($j = 0; $j < $usersToday; $j++) {
                User::create([
                    'name' => fake()->name(),
                    'username' => fake()->unique()->userName() . rand(1000, 9999),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => Hash::make('12345678'),
                    'email_verified_at' => $date,
                    'bio' => fake()->sentence(),
                    'gender' => fake()->randomElement(['male', 'female', 'other']),
                    'is_active' => rand(0, 10) > 2, // 80% active
                    'is_verified' => rand(0, 10) > 7, // 30% verified
                    'created_at' => $date,
                    'updated_at' => $date,
                    'last_login_at' => rand(0, 10) > 3 ? fake()->dateTimeBetween($date, 'now') : null,
                    'last_activity_at' => rand(0, 10) > 5 ? fake()->dateTimeBetween($date, 'now') : null,
                ]);
            }
        }
    }

    private function seedPosts()
    {
        $this->command->info('Creating sample posts...');

        $users = User::pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            $postsToday = rand(2, 8); // Reduced number

            for ($j = 0; $j < $postsToday; $j++) {
                Post::create([
                    'user_id' => fake()->randomElement($users),
                    'social_circle_id' => 1, // Use default social circle
                    'content' => fake()->paragraph(),
                    'type' => fake()->randomElement(['text', 'image', 'video']),
                    'is_published' => rand(0, 10) > 1, // 90% published
                    'published_at' => $date,
                    'likes_count' => rand(0, 50),
                    'comments_count' => rand(0, 20),
                    'shares_count' => rand(0, 10),
                    'views_count' => rand(10, 200),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }

    private function seedStreams()
    {
        $this->command->info('Creating sample streams...');

        $users = User::pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            $streamsToday = rand(0, 3);

            for ($j = 0; $j < $streamsToday; $j++) {
                $status = fake()->randomElement(['upcoming', 'live', 'ended']);
                $isPaid = rand(0, 10) > 7; // 30% paid streams

                Stream::create([
                    'user_id' => fake()->randomElement($users),
                    'channel_name' => 'stream_' . fake()->unique()->randomNumber(8),
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'status' => $status,
                    'is_paid' => $isPaid,
                    'price' => $isPaid ? fake()->randomFloat(2, 5, 50) : 0,
                    'currency' => 'USD',
                    'current_viewers' => $status === 'live' ? rand(0, 100) : 0,
                    'max_viewers' => rand(50, 500),
                    'stream_type' => 'immediate',
                    'created_at' => $date,
                    'updated_at' => $date,
                    'started_at' => in_array($status, ['live', 'ended']) ? $date : null,
                    'ended_at' => $status === 'ended' ? $date->copy()->addHours(rand(1, 4)) : null,
                ]);
            }
        }
    }

    private function seedSubscriptions()
    {
        $this->command->info('Creating sample subscriptions...');

        $users = User::pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            $subscriptionsToday = rand(0, 8);

            for ($j = 0; $j < $subscriptionsToday; $j++) {
                $amount = fake()->randomElement([9.99, 19.99, 49.99, 99.99]);
                $status = fake()->randomElement(['active', 'expired', 'cancelled']);

                UserSubscription::create([
                    'user_id' => fake()->randomElement($users),
                    'subscription_id' => 1, // Assuming subscription ID 1 exists
                    'amount' => $amount,
                    'currency' => 'USD',
                    'payment_method' => fake()->randomElement(['stripe', 'paypal']),
                    'payment_status' => $status === 'active' ? 'completed' : 'failed',
                    'status' => $status,
                    'started_at' => $date,
                    'expires_at' => $status === 'active' ? $date->copy()->addMonth() : $date->copy()->addDays(rand(1, 30)),
                    'created_at' => $date,
                    'updated_at' => $date,
                    'paid_at' => $date,
                    'current_period_start' => $date,
                    'current_period_end' => $date->copy()->addMonth(),
                ]);
            }
        }
    }

    private function seedAds()
    {
        $this->command->info('Creating sample ads...');

        $users = User::pluck('id')->toArray();

        for ($i = 0; $i < 15; $i++) {
            $date = Carbon::now()->subDays($i * 2);
            $adsToday = rand(1, 3);

            for ($j = 0; $j < $adsToday; $j++) {
                $status = fake()->randomElement(['active', 'pending_review', 'rejected', 'draft']);
                $budget = fake()->randomFloat(2, 100, 5000);

                Ad::create([
                    'user_id' => fake()->randomElement($users),
                    'ad_name' => fake()->sentence(3),
                    'type' => fake()->randomElement(['banner', 'video', 'story']),
                    'description' => fake()->paragraph(),
                    'status' => $status,
                    'admin_status' => $status === 'active' ? 'approved' : 'pending',
                    'budget' => $budget,
                    'total_spent' => $status === 'active' ? $budget * (rand(10, 80) / 100) : 0,
                    'target_audience' => json_encode([
                        'age_min' => rand(18, 30),
                        'age_max' => rand(31, 65),
                        'gender' => fake()->randomElement(['all', 'male', 'female']),
                    ]),
                    'start_date' => $date,
                    'end_date' => $date->copy()->addDays(rand(7, 30)),
                    'current_impressions' => $status === 'active' ? rand(1000, 10000) : 0,
                    'clicks' => $status === 'active' ? rand(50, 500) : 0,
                    'deleted_flag' => 'N',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }

    private function seedStories()
    {
        $this->command->info('Creating sample stories...');

        $users = User::pluck('id')->toArray();

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $storiesToday = rand(10, 30);

            for ($j = 0; $j < $storiesToday; $j++) {
                $expiresAt = $date->copy()->addHours(24);
                $isExpired = $expiresAt->isPast();

                Story::create([
                    'user_id' => fake()->randomElement($users),
                    'type' => fake()->randomElement(['text', 'image', 'video']),
                    'content' => fake()->sentence(),
                    'caption' => fake()->optional()->sentence(),
                    'background_color' => fake()->hexColor(),
                    'privacy' => fake()->randomElement(['all_connections', 'custom']),
                    'allow_replies' => rand(0, 10) > 3, // 70% allow replies
                    'views_count' => rand(5, 150),
                    'expires_at' => $expiresAt,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
