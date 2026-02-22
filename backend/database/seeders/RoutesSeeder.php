<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [
            // Japan to Uganda Routes
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Tokyo',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 45,
                'base_price' => 2500.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Yokohama',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 42,
                'base_price' => 2400.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Osaka',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 48,
                'base_price' => 2600.00,
                'is_active' => true,
            ],
            
            // UK to Uganda Routes
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'London',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 35,
                'base_price' => 2200.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'Southampton',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 32,
                'base_price' => 2100.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'Liverpool',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 38,
                'base_price' => 2300.00,
                'is_active' => true,
            ],
            
            // UAE to Uganda Routes
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Dubai',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 21,
                'base_price' => 1800.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Abu Dhabi',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 25,
                'base_price' => 1900.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Sharjah',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 23,
                'base_price' => 1850.00,
                'is_active' => true,
            ],
        ];

        foreach ($routes as $route) {
            DB::table('routes')->updateOrInsert(
                [
                    'origin_country' => $route['origin_country'],
                    'origin_city' => $route['origin_city'],
                    'destination_country' => $route['destination_country'],
                    'destination_city' => $route['destination_city']
                ],
                array_merge($route, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}