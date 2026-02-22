<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'vehicle_type_id' => 1, // We'll use a simple ID for now
            'make' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes', 'Audi']),
            'model' => $this->faker->randomElement(['Camry', 'Accord', 'F-150', 'X5', 'C-Class', 'A4']),
            'year' => $this->faker->numberBetween(2000, 2024),
            'color' => $this->faker->colorName(),
            'vin' => $this->faker->unique()->regexify('[A-Z0-9]{17}'),
            'license_plate' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'engine_type' => $this->faker->randomElement(['petrol', 'diesel', 'hybrid', 'electric']),
            'transmission' => $this->faker->randomElement(['automatic', 'manual']),
            'is_running' => $this->faker->boolean(90), // 90% chance of being running
            'length' => $this->faker->numberBetween(12, 25), // feet
            'width' => $this->faker->numberBetween(5, 8), // feet
            'height' => $this->faker->numberBetween(4, 8), // feet
            'weight' => $this->faker->numberBetween(2000, 8000), // pounds
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_running' => true,
        ]);
    }

    public function notRunning(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_running' => false,
        ]);
    }
}