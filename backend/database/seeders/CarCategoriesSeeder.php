<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarCategory;

class CarCategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Sedans',
                'description' => 'Four-door passenger cars with separate trunk',
                'icon' => 'FaCar',
                'sort_order' => 1
            ],
            [
                'name' => 'SUVs',
                'description' => 'Sport Utility Vehicles with higher ground clearance',
                'icon' => 'FaTruck',
                'sort_order' => 2
            ],
            [
                'name' => 'Hatchbacks',
                'description' => 'Compact cars with rear door that opens upwards',
                'icon' => 'FaCar',
                'sort_order' => 3
            ],
            [
                'name' => 'Wagons',
                'description' => 'Extended passenger cars with cargo area',
                'icon' => 'FaCar',
                'sort_order' => 4
            ],
            [
                'name' => 'Coupes',
                'description' => 'Two-door cars with sporty design',
                'icon' => 'FaCar',
                'sort_order' => 5
            ],
            [
                'name' => 'Convertibles',
                'description' => 'Cars with retractable or removable roof',
                'icon' => 'FaCar',
                'sort_order' => 6
            ],
            [
                'name' => 'Trucks',
                'description' => 'Pickup trucks and commercial vehicles',
                'icon' => 'FaIndustry',
                'sort_order' => 7
            ],
            [
                'name' => 'Vans',
                'description' => 'Multi-purpose vehicles and minivans',
                'icon' => 'FaTruck',
                'sort_order' => 8
            ],
            [
                'name' => 'Motorcycles',
                'description' => 'Two-wheeled motor vehicles',
                'icon' => 'FaMotorcycle',
                'sort_order' => 9
            ],
            [
                'name' => 'Luxury',
                'description' => 'Premium and luxury vehicles',
                'icon' => 'FaCar',
                'sort_order' => 10
            ],
        ];

        foreach ($categories as $category) {
            CarCategory::create($category);
        }
    }
}