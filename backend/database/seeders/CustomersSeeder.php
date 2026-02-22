<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Create some specific test customers
        $testCustomers = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+256-700-123-001',
                'password' => Hash::make('customer123'),
                'country' => 'Uganda',
                'city' => 'Kampala',
                'address' => 'Plot 123, Nakasero Road',
                'postal_code' => '00256',
                'date_of_birth' => '1985-06-15',
                'id_number' => 'CM123456789',
                'id_type' => 'national_id',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'total_bookings' => 3,
                'total_spent' => 7500.00,
                'last_login_at' => now()->subDays(2),
                'preferred_language' => 'en',
                'newsletter_subscribed' => true,
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => '+256-700-123-002',
                'password' => Hash::make('customer123'),
                'country' => 'Uganda',
                'city' => 'Entebbe',
                'address' => 'Plot 456, Airport Road',
                'postal_code' => '00256',
                'date_of_birth' => '1990-03-22',
                'id_number' => 'PP987654321',
                'id_type' => 'passport',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'total_bookings' => 1,
                'total_spent' => 2400.00,
                'last_login_at' => now()->subDays(5),
                'preferred_language' => 'en',
                'newsletter_subscribed' => true,
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Ssemakula',
                'email' => 'michael.ssemakula@example.com',
                'phone' => '+256-700-123-003',
                'password' => Hash::make('customer123'),
                'country' => 'Uganda',
                'city' => 'Jinja',
                'address' => 'Plot 789, Main Street',
                'postal_code' => '00256',
                'date_of_birth' => '1982-11-08',
                'id_number' => 'CM567890123',
                'id_type' => 'national_id',
                'is_verified' => false,
                'is_active' => true,
                'email_verified_at' => null,
                'total_bookings' => 0,
                'total_spent' => 0.00,
                'last_login_at' => now()->subDays(1),
                'preferred_language' => 'en',
                'newsletter_subscribed' => false,
            ],
        ];

        foreach ($testCustomers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['email' => $customer['email']],
                array_merge($customer, [
                    'created_at' => now()->subDays(rand(30, 365)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ])
            );
        }

        // Create additional random customers for testing
        for ($i = 0; $i < 20; $i++) {
            $createdAt = $faker->dateTimeBetween('-1 year', '-1 month');
            $updatedAt = $faker->dateTimeBetween($createdAt, 'now');
            
            // Randomly assign status with weighted distribution
            $statusRand = $faker->numberBetween(1, 100);
            if ($statusRand <= 85) {
                $status = 'active';
                $isActive = true;
            } elseif ($statusRand <= 95) {
                $status = 'inactive';
                $isActive = false;
            } else {
                $status = 'suspended';
                $isActive = false;
            }
            
            DB::table('customers')->insert([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'phone' => '+256-' . $faker->numerify('7##-###-###'),
                'password' => Hash::make('customer123'),
                'country' => $faker->randomElement(['Uganda', 'Kenya', 'Tanzania', 'Rwanda']),
                'city' => $faker->city,
                'address' => $faker->address,
                'postal_code' => $faker->postcode,
                'date_of_birth' => $faker->date('Y-m-d', '-18 years'),
                'id_number' => $faker->randomElement(['CM', 'PP']) . $faker->numerify('#########'),
                'id_type' => $faker->randomElement(['national_id', 'passport', 'drivers_license']),
                'is_verified' => $faker->boolean(70),
                'is_active' => $isActive,
                'status' => $status,
                'email_verified_at' => $faker->boolean(80) ? $faker->dateTimeBetween('-1 year', 'now') : null,
                'total_bookings' => $faker->numberBetween(0, 10),
                'total_spent' => $faker->randomFloat(2, 0, 25000),
                'last_login_at' => $faker->boolean(60) ? $faker->dateTimeBetween('-1 month', 'now') : null,
                'preferred_language' => $faker->randomElement(['en', 'sw']),
                'newsletter_subscribed' => $faker->boolean(60),
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }
}