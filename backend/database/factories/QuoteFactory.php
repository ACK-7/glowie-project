<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'route_id' => Route::factory(),
            'quote_reference' => 'QT' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'vehicle_details' => json_encode([
                'make' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford']),
                'model' => $this->faker->randomElement(['Camry', 'Accord', 'F-150']),
                'year' => $this->faker->numberBetween(2000, 2024),
                'type' => $this->faker->randomElement(['sedan', 'suv', 'truck']),
            ]),
            'base_price' => $this->faker->randomFloat(2, 500, 3000),
            'additional_fees' => json_encode([
                'insurance' => $this->faker->randomFloat(2, 50, 300),
                'fuel_surcharge' => $this->faker->randomFloat(2, 25, 100),
            ]),
            'total_amount' => $this->faker->randomFloat(2, 600, 3500),
            'currency' => 'USD',
            'valid_until' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'converted', 'expired']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_until' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
        ]);
    }
}