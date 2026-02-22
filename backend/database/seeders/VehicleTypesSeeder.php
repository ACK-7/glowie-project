<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            // Japanese Vehicles
            [
                'vehicle_type_id' => 1, // Default to 1 for now
                'make' => 'Toyota',
                'model' => 'Prius',
                'year' => 2020,
                'color' => 'White',
                'engine_type' => 'hybrid',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 1,
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2019,
                'color' => 'Blue',
                'engine_type' => 'petrol',
                'transmission' => 'manual',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'Nissan',
                'model' => 'X-Trail',
                'year' => 2021,
                'color' => 'Black',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'Mazda',
                'model' => 'CX-5',
                'year' => 2020,
                'color' => 'Red',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'Subaru',
                'model' => 'Forester',
                'year' => 2019,
                'color' => 'Silver',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            
            // UK Vehicles
            [
                'vehicle_type_id' => 2,
                'make' => 'Land Rover',
                'model' => 'Discovery',
                'year' => 2020,
                'color' => 'Green',
                'engine_type' => 'diesel',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 1,
                'make' => 'Jaguar',
                'model' => 'XE',
                'year' => 2019,
                'color' => 'Black',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'Range Rover',
                'model' => 'Evoque',
                'year' => 2021,
                'color' => 'White',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            
            // UAE Vehicles
            [
                'vehicle_type_id' => 1,
                'make' => 'Mercedes-Benz',
                'model' => 'C-Class',
                'year' => 2020,
                'color' => 'Silver',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'BMW',
                'model' => 'X3',
                'year' => 2021,
                'color' => 'Blue',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 1,
                'make' => 'Audi',
                'model' => 'A4',
                'year' => 2019,
                'color' => 'Gray',
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
            [
                'vehicle_type_id' => 2,
                'make' => 'Lexus',
                'model' => 'RX',
                'year' => 2020,
                'color' => 'White',
                'engine_type' => 'hybrid',
                'transmission' => 'automatic',
                'is_running' => true,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            DB::table('vehicles')->updateOrInsert(
                [
                    'make' => $vehicle['make'],
                    'model' => $vehicle['model'],
                    'year' => $vehicle['year']
                ],
                array_merge($vehicle, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}