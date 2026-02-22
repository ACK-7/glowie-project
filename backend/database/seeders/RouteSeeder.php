<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Tokyo', // Generic port city
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'base_price' => 1500.00,
                'estimated_days' => 45,
                'is_active' => true,
            ],
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'Southampton',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'base_price' => 1800.00,
                'estimated_days' => 35,
                'is_active' => true,
            ],
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Dubai',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'base_price' => 1100.00,
                'estimated_days' => 25,
                'is_active' => true,
            ],
        ];

        foreach ($routes as $route) {
            Route::firstOrCreate(
                [
                    'origin_country' => $route['origin_country'],
                    'destination_country' => $route['destination_country']
                ],
                $route
            );
        }
        
        $this->command->info('Routes seeded successfully!');
    }
}
