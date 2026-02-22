<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ShipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get bookings that should have shipments (confirmed, in_transit, delivered)
        $bookings = DB::table('bookings')
            ->whereIn('status', ['confirmed', 'in_transit', 'delivered'])
            ->get()
            ->toArray();

        if (empty($bookings)) {
            return; // Skip if no eligible bookings exist
        }

        foreach ($bookings as $booking) {
            $createdAt = $faker->dateTimeBetween($booking->created_at, 'now');
            
            // Determine shipment status based on booking status
            $status = match($booking->status) {
                'confirmed' => $faker->randomElement(['preparing', 'in_transit']),
                'in_transit' => $faker->randomElement(['in_transit', 'customs']),
                'delivered' => 'delivered',
                default => 'preparing'
            };
            
            $departureDate = null;
            $estimatedArrival = null;
            $actualArrival = null;
            
            if (in_array($status, ['in_transit', 'customs', 'delivered'])) {
                $departureDate = $faker->dateTimeBetween($createdAt, '+1 week');
                $estimatedArrival = $faker->dateTimeBetween($departureDate, '+2 months');
                
                if ($status === 'delivered') {
                    $actualArrival = $faker->dateTimeBetween($departureDate, $estimatedArrival);
                }
            }
            
            $trackingUpdates = [];
            $currentLocation = 'Origin Port';
            
            // Generate tracking updates based on status
            switch ($status) {
                case 'preparing':
                    $trackingUpdates[] = [
                        'timestamp' => $createdAt->format('Y-m-d H:i:s'),
                        'location' => 'Origin Port',
                        'status' => 'Vehicle received at port',
                        'description' => 'Vehicle has been received and is being prepared for shipment'
                    ];
                    break;
                    
                case 'in_transit':
                    $trackingUpdates[] = [
                        'timestamp' => $createdAt->format('Y-m-d H:i:s'),
                        'location' => 'Origin Port',
                        'status' => 'Departed',
                        'description' => 'Vehicle has departed from origin port'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s'),
                        'location' => 'At Sea',
                        'status' => 'In Transit',
                        'description' => 'Vehicle is currently in transit'
                    ];
                    $currentLocation = 'At Sea - Indian Ocean';
                    break;
                    
                case 'customs':
                    $trackingUpdates[] = [
                        'timestamp' => $createdAt->format('Y-m-d H:i:s'),
                        'location' => 'Origin Port',
                        'status' => 'Departed',
                        'description' => 'Vehicle has departed from origin port'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s'),
                        'location' => 'Mombasa Port',
                        'status' => 'Arrived',
                        'description' => 'Vehicle has arrived at Mombasa Port'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s'),
                        'location' => 'Customs Office',
                        'status' => 'Customs Clearance',
                        'description' => 'Vehicle is undergoing customs clearance'
                    ];
                    $currentLocation = 'Mombasa - Customs Office';
                    break;
                    
                case 'delivered':
                    $trackingUpdates[] = [
                        'timestamp' => $createdAt->format('Y-m-d H:i:s'),
                        'location' => 'Origin Port',
                        'status' => 'Departed',
                        'description' => 'Vehicle has departed from origin port'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s'),
                        'location' => 'Mombasa Port',
                        'status' => 'Arrived',
                        'description' => 'Vehicle has arrived at Mombasa Port'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d H:i:s'),
                        'location' => 'Customs Office',
                        'status' => 'Customs Cleared',
                        'description' => 'Vehicle has cleared customs'
                    ];
                    $trackingUpdates[] = [
                        'timestamp' => $actualArrival->format('Y-m-d H:i:s'),
                        'location' => $booking->recipient_city,
                        'status' => 'Delivered',
                        'description' => 'Vehicle has been delivered to customer'
                    ];
                    $currentLocation = $booking->recipient_city . ', Uganda';
                    break;
            }

            DB::table('shipments')->insert([
                'tracking_number' => 'TRK' . str_pad($booking->id, 8, '0', STR_PAD_LEFT),
                'booking_id' => $booking->id,
                'carrier_name' => $faker->randomElement([
                    'Maersk Line',
                    'MSC Mediterranean Shipping',
                    'CMA CGM',
                    'COSCO Shipping',
                    'Hapag-Lloyd'
                ]),
                'vessel_name' => $faker->randomElement([
                    'MV Ocean Pioneer',
                    'MV Sea Explorer',
                    'MV Atlantic Voyager',
                    'MV Pacific Navigator',
                    'MV Indian Ocean Star'
                ]),
                'container_number' => $faker->regexify('[A-Z]{4}[0-9]{7}'),
                'current_location' => $currentLocation,
                'status' => $status,
                'departure_port' => $faker->randomElement([
                    'Port of Tokyo',
                    'Port of Yokohama',
                    'Port of London',
                    'Port Rashid Dubai'
                ]),
                'arrival_port' => 'Port Bell, Uganda',
                'departure_date' => $departureDate,
                'estimated_arrival' => $estimatedArrival,
                'actual_arrival' => $actualArrival,
                'tracking_updates' => json_encode($trackingUpdates),
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
            ]);
        }
    }
}