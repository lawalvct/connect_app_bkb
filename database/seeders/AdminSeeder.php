<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::firstOrCreate([
            'email' => 'admin@connectapp.com'
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'status' => 'active'
        ]);

        $this->command->info('Admin account created/updated:');
        $this->command->info('Email: admin@connectapp.com');
        $this->command->info('Password: admin123');
    }
}
