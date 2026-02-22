<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\CarBrand;
use App\Models\CarCategory;

class CarsSeeder extends Seeder
{
    public function run()
    {
        // Get brand and category IDs
        $toyota = CarBrand::where('name', 'Toyota')->first();
        $honda = CarBrand::where('name', 'Honda')->first();
        $bmw = CarBrand::where('name', 'BMW')->first();
        $mercedes = CarBrand::where('name', 'Mercedes-Benz')->first();
        $nissan = CarBrand::where('name', 'Nissan')->first();
        $audi = CarBrand::where('name', 'Audi')->first();

        $sedans = CarCategory::where('name', 'Sedans')->first();
        $suvs = CarCategory::where('name', 'SUVs')->first();
        $luxury = CarCategory::where('name', 'Luxury')->first();

        $cars = [
            [
                'brand_id' => $toyota->id,
                'category_id' => $suvs->id,
                'model' => 'Land Cruiser',
                'year' => 2021,
                'color' => 'White',
                'vin' => 'JTMCY7AJ5M4123456',
                'description' => 'Legendary off-road capability meets luxury comfort. This Land Cruiser features advanced 4WD system, premium interior, and Toyota\'s renowned reliability.',
                'engine_type' => '4.6L V8',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'mileage' => 20000,
                'drive_type' => '4wd',
                'doors' => 5,
                'seats' => 8,
                'price' => 35000.00,
                'currency' => 'USD',
                'location_country' => 'Japan',
                'location_city' => 'Tokyo',
                'condition' => 'used',
                'is_featured' => true,
                'is_running' => true,
                'features' => ['Leather Seats', 'Navigation System', 'Sunroof', 'Backup Camera', 'Bluetooth'],
                'safety_features' => ['ABS', 'Airbags', 'Stability Control', 'Traction Control'],
                'tags' => ['reliable', 'off-road', 'family', 'luxury'],
                'rating' => 4.8,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
            [
                'brand_id' => $honda->id,
                'category_id' => $suvs->id,
                'model' => 'CR-V',
                'year' => 2020,
                'color' => 'Silver',
                'vin' => '2HKRM4H75MH123456',
                'description' => 'Honda\'s best-selling SUV combines fuel efficiency with versatility. Perfect for city driving and weekend adventures.',
                'engine_type' => '1.5L Turbo',
                'fuel_type' => 'hybrid',
                'transmission' => 'cvt',
                'mileage' => 30000,
                'drive_type' => 'awd',
                'doors' => 5,
                'seats' => 5,
                'price' => 28000.00,
                'currency' => 'USD',
                'location_country' => 'Japan',
                'location_city' => 'Osaka',
                'condition' => 'used',
                'is_featured' => true,
                'is_running' => true,
                'features' => ['Honda Sensing', 'Apple CarPlay', 'Android Auto', 'Heated Seats'],
                'safety_features' => ['Collision Mitigation', 'Lane Keeping Assist', 'Adaptive Cruise Control'],
                'tags' => ['fuel-efficient', 'reliable', 'family', 'hybrid'],
                'rating' => 4.7,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
            [
                'brand_id' => $bmw->id,
                'category_id' => $luxury->id,
                'model' => 'X5',
                'year' => 2021,
                'color' => 'Black',
                'vin' => '5UXCR6C09M9123456',
                'description' => 'The ultimate driving machine in SUV form. Combines BMW\'s signature performance with luxury and practicality.',
                'engine_type' => '3.0L Twin-Turbo I6',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'mileage' => 15000,
                'drive_type' => 'awd',
                'doors' => 5,
                'seats' => 7,
                'price' => 42000.00,
                'currency' => 'USD',
                'location_country' => 'UK',
                'location_city' => 'London',
                'condition' => 'used',
                'is_featured' => true,
                'is_running' => true,
                'features' => ['iDrive System', 'Panoramic Sunroof', 'Premium Audio', 'Wireless Charging'],
                'safety_features' => ['Active Protection', 'Blind Spot Detection', 'Park Assist'],
                'tags' => ['luxury', 'performance', 'premium', 'german'],
                'rating' => 4.9,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
            [
                'brand_id' => $nissan->id,
                'category_id' => $suvs->id,
                'model' => 'Patrol',
                'year' => 2019,
                'color' => 'Gray',
                'vin' => 'JN8AZ2NE5K9123456',
                'description' => 'Powerful full-size SUV built for the toughest conditions. Perfect for large families and heavy-duty use.',
                'engine_type' => '5.6L V8',
                'fuel_type' => 'diesel',
                'transmission' => 'automatic',
                'mileage' => 25000,
                'drive_type' => '4wd',
                'doors' => 5,
                'seats' => 8,
                'price' => 31000.00,
                'currency' => 'USD',
                'location_country' => 'UAE',
                'location_city' => 'Dubai',
                'condition' => 'used',
                'is_featured' => false,
                'is_running' => true,
                'features' => ['7-inch Display', 'Rear Entertainment', 'Climate Control', 'Tow Package'],
                'safety_features' => ['Vehicle Dynamic Control', 'Hill Start Assist', 'Tire Pressure Monitor'],
                'tags' => ['powerful', 'family', 'towing', 'spacious'],
                'rating' => 4.6,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
            [
                'brand_id' => $mercedes->id,
                'category_id' => $luxury->id,
                'model' => 'GLE 450',
                'year' => 2020,
                'color' => 'Blue',
                'vin' => '4JGDF7DE5LA123456',
                'description' => 'Mercedes-Benz luxury SUV with cutting-edge technology and supreme comfort. The perfect blend of performance and elegance.',
                'engine_type' => '3.0L Turbo I6',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'mileage' => 18000,
                'drive_type' => 'awd',
                'doors' => 5,
                'seats' => 5,
                'price' => 38000.00,
                'currency' => 'USD',
                'location_country' => 'UK',
                'location_city' => 'Manchester',
                'condition' => 'used',
                'is_featured' => true,
                'is_running' => true,
                'features' => ['MBUX System', 'Air Suspension', 'Premium Sound', 'Ambient Lighting'],
                'safety_features' => ['Pre-Safe System', 'Active Brake Assist', 'Attention Assist'],
                'tags' => ['luxury', 'comfort', 'technology', 'premium'],
                'rating' => 4.8,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
            [
                'brand_id' => $audi->id,
                'category_id' => $luxury->id,
                'model' => 'Q7',
                'year' => 2022,
                'color' => 'White',
                'vin' => 'WA1LMAF77MD123456',
                'description' => 'Latest generation Audi Q7 with advanced quattro all-wheel drive and luxurious three-row seating.',
                'engine_type' => '3.0L TFSI V6',
                'fuel_type' => 'hybrid',
                'transmission' => 'automatic',
                'mileage' => 12000,
                'drive_type' => 'awd',
                'doors' => 5,
                'seats' => 7,
                'price' => 45000.00,
                'currency' => 'USD',
                'location_country' => 'UK',
                'location_city' => 'Birmingham',
                'condition' => 'certified_pre_owned',
                'is_featured' => true,
                'is_running' => true,
                'features' => ['Virtual Cockpit', 'Matrix LED Headlights', 'Bang & Olufsen Audio', 'Massage Seats'],
                'safety_features' => ['Audi Pre Sense', 'Side Assist', 'Exit Warning'],
                'tags' => ['new-arrival', 'luxury', 'hybrid', 'technology'],
                'rating' => 4.9,
                'images' => [
                    ['image_url' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&auto=format&fit=crop', 'is_primary' => true, 'type' => 'exterior'],
                ]
            ],
        ];

        foreach ($cars as $carData) {
            $images = $carData['images'];
            unset($carData['images']);
            
            $car = Car::create($carData);
            
            // Add images
            foreach ($images as $index => $imageData) {
                CarImage::create([
                    'car_id' => $car->id,
                    'image_url' => $imageData['image_url'],
                    'alt_text' => $car->full_name,
                    'type' => $imageData['type'],
                    'is_primary' => $imageData['is_primary'],
                    'sort_order' => $index,
                ]);
            }
        }
    }
}