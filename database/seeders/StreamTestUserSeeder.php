<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StreamTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Create test admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'username' => 'admin_test',
                'name' => 'Admin User',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'state' => 'California',
                'bio' => 'Test admin user for streaming',
                'timezone' => 'America/New_York',
            ]
        );
        
        // Assign admin role
        $admin->assignRole('admin');
        
        // Create test regular user
        $user = User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'username' => 'user_test',
                'name' => 'Regular User',
                'email' => 'user@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'birth_date' => '1995-01-01',
                'gender' => 'female',
                'state' => 'New York',
                'bio' => 'Test regular user for streaming',
                'timezone' => 'America/New_York',
            ]
        );
        
        $this->command->info('Test users created:');
        $this->command->info('Admin: admin@test.com / password123');
        $this->command->info('User: user@test.com / password123');
    }
}
