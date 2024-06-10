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
        // Seed the roles table
        $this->call(RolesTableSeeder::class);

        // Uncomment and add other seeders below if needed
        // $this->call(UserDetailsTableSeeder::class);
        // $this->call(UserRolesTableSeeder::class);
         $this->call(DefaultUserSeeder::class);
        // $this->call(AnotherTableSeeder::class);
    }
}
