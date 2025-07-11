<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountriesTableSeeder::class,
            SocialCirclesTableSeeder::class,
            UsersTableSeeder::class,
            SettingSeeder::class,
            AdSeeder::class,
        ]);
    }
}
