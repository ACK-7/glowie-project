<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the enhanced database seeder for the admin dashboard system
        $this->call([
            EnhancedDatabaseSeeder::class,
            CarBrandsSeeder::class,
            CarCategoriesSeeder::class,
            CarsSeeder::class,
        ]);
        
        // Original factory examples (commented out)
        // \App\Models\User::factory(10)->create();
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
