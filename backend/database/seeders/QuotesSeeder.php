<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class QuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get existing customers, vehicles, and routes
        $customers = DB::table('customers')->pluck('id')->toArray();
        $vehicles = DB::table('vehicles')->get()->toArray();
        $routes = DB::table('routes')->get()->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($customers) || empty($vehicles) || empty($routes)) {
            return; // Skip if no related data exists
        }

        for ($i = 0; $i < 50; $i++) {
            $customer = $faker->randomElement($customers);
            $vehicle = $faker->randomElement($vehicles);
            $route = $faker->randomElement($routes);
            $createdAt = $faker->dateTimeBetween('-6 months', 'now');
            $validUntil = $faker->dateTimeBetween($createdAt, '+2 months');
            
            $basePrice = $route->base_price + $faker->randomFloat(2, -500, 1000);
            $additionalFees = [
                'insurance' => $faker->randomFloat(2, 100, 500),
                'handling' => $faker->randomFloat(2, 50, 200),
                'documentation' => $faker->randomFloat(2, 25, 100),
            ];
            $totalAmount = $basePrice + array_sum($additionalFees);
            
            $status = $faker->randomElement(['pending', 'approved', 'rejected', 'converted', 'expired']);
            $approvedBy = null;
            $approvedAt = null;
            
            if (in_array($status, ['approved', 'converted'])) {
                $approvedBy = $faker->randomElement($users);
                $approvedAt = $faker->dateTimeBetween($createdAt, 'now');
            }

            DB::table('quotes')->insert([
                'quote_reference' => 'QT' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'customer_id' => $customer,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
                'vehicle_details' => json_encode([
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color ?? 'Unknown',
                    'engine_type' => $vehicle->engine_type,
                    'transmission' => $vehicle->transmission,
                    'estimated_value' => $faker->randomFloat(2, 5000, 50000),
                    'vin' => $vehicle->vin ?? $faker->regexify('[A-Z0-9]{17}'),
                ]),
                'base_price' => $basePrice,
                'additional_fees' => json_encode($additionalFees),
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'valid_until' => $validUntil->format('Y-m-d'),
                'notes' => $faker->boolean(30) ? $faker->sentence() : null,
                'created_by' => $faker->randomElement($users),
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
            ]);
        }
    }
}