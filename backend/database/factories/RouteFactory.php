<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'origin_country' => $this->faker->country(),
            'origin_city' => $this->faker->city(),
            'destination_country' => $this->faker->country(),
            'destination_city' => $this->faker->city(),
            'base_price' => $this->faker->randomFloat(2, 500, 3000),
            'estimated_days' => $this->faker->numberBetween(3, 14),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}