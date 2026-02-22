<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class BookingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get existing data
        $users = DB::table('users')->pluck('id')->toArray();
        $vehicles = DB::table('vehicles')->pluck('id')->toArray();
        $routes = DB::table('routes')->pluck('id')->toArray();
        $convertedQuotes = DB::table('quotes')->where('status', 'converted')->pluck('id')->toArray();

        if (empty($users) || empty($vehicles) || empty($routes)) {
            return; // Skip if no related data exists
        }

        for ($i = 0; $i < 30; $i++) {
            $customer = $faker->randomElement($users);
            $vehicle = $faker->randomElement($vehicles);
            $route = $faker->randomElement($routes);
            $quoteId = $faker->boolean(60) && !empty($convertedQuotes) ? $faker->randomElement($convertedQuotes) : null;
            
            $createdAt = $faker->dateTimeBetween('-4 months', 'now');
            $pickupDate = $faker->dateTimeBetween($createdAt, '+1 month');
            $estimatedDelivery = $faker->dateTimeBetween($pickupDate, '+2 months');
            
            $status = $faker->randomElement(['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled']);
            $deliveryDate = null;
            
            if ($status === 'delivered') {
                $deliveryDate = $faker->dateTimeBetween($pickupDate, $estimatedDelivery);
            }
            
            $totalAmount = $faker->randomFloat(2, 1500, 8000);
            $paidAmount = 0;
            
            switch ($status) {
                case 'confirmed':
                case 'in_transit':
                    $paidAmount = $totalAmount * 0.5; // 50% deposit
                    break;
                case 'delivered':
                    $paidAmount = $totalAmount; // Full payment
                    break;
                case 'cancelled':
                    $paidAmount = $faker->boolean(30) ? $totalAmount * 0.1 : 0; // Sometimes cancellation fee
                    break;
            }

            DB::table('bookings')->insert([
                'booking_reference' => 'BK' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'customer_id' => $customer,
                'quote_id' => $quoteId,
                'vehicle_id' => $vehicle,
                'route_id' => $route,
                'status' => $status,
                'pickup_date' => $pickupDate,
                'delivery_date' => $deliveryDate,
                'estimated_delivery' => $estimatedDelivery,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'currency' => 'USD',
                'notes' => $faker->boolean(40) ? $faker->sentence() : null,
                'created_by' => $faker->randomElement($users),
                'updated_by' => $faker->randomElement($users),
                'special_instructions' => $faker->boolean(30) ? $faker->sentence() : null,
                'recipient_name' => $faker->name,
                'recipient_phone' => '+256-' . $faker->numerify('7##-###-###'),
                'recipient_email' => $faker->safeEmail,
                'recipient_country' => 'Uganda',
                'recipient_city' => $faker->randomElement(['Kampala', 'Entebbe', 'Jinja', 'Mbarara', 'Gulu']),
                'recipient_address' => $faker->address,
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
            ]);
        }
    }
}