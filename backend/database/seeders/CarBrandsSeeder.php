<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarBrand;

class CarBrandsSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            ['name' => 'Toyota', 'country_of_origin' => 'Japan', 'sort_order' => 1],
            ['name' => 'Honda', 'country_of_origin' => 'Japan', 'sort_order' => 2],
            ['name' => 'BMW', 'country_of_origin' => 'Germany', 'sort_order' => 3],
            ['name' => 'Mercedes-Benz', 'country_of_origin' => 'Germany', 'sort_order' => 4],
            ['name' => 'Audi', 'country_of_origin' => 'Germany', 'sort_order' => 5],
            ['name' => 'Nissan', 'country_of_origin' => 'Japan', 'sort_order' => 6],
            ['name' => 'Mitsubishi', 'country_of_origin' => 'Japan', 'sort_order' => 7],
            ['name' => 'Subaru', 'country_of_origin' => 'Japan', 'sort_order' => 8],
            ['name' => 'Mazda', 'country_of_origin' => 'Japan', 'sort_order' => 9],
            ['name' => 'Volkswagen', 'country_of_origin' => 'Germany', 'sort_order' => 10],
            ['name' => 'Ford', 'country_of_origin' => 'USA', 'sort_order' => 11],
            ['name' => 'Chevrolet', 'country_of_origin' => 'USA', 'sort_order' => 12],
            ['name' => 'Lexus', 'country_of_origin' => 'Japan', 'sort_order' => 13],
            ['name' => 'Infiniti', 'country_of_origin' => 'Japan', 'sort_order' => 14],
            ['name' => 'Acura', 'country_of_origin' => 'Japan', 'sort_order' => 15],
        ];

        foreach ($brands as $brand) {
            CarBrand::create($brand);
        }
    }
}