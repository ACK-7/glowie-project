<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'quote_id' => Quote::factory(),
            'vehicle_id' => Vehicle::factory(),
            'route_id' => Route::factory(),
            'reference_number' => 'BK' . $this->faker->unique()->numerify('######'),
            'booking_reference' => 'BK' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'pickup_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'delivery_date_estimated' => $this->faker->dateTimeBetween('+31 days', '+60 days'),
            'delivery_date_actual' => null,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled']),
            'special_instructions' => $this->faker->optional()->sentence(),
            'recipient_name' => $this->faker->name(),
            'recipient_phone' => $this->faker->phoneNumber(),
            'recipient_email' => $this->faker->safeEmail(),
            'recipient_country' => $this->faker->country(),
            'recipient_city' => $this->faker->city(),
            'recipient_address' => $this->faker->address(),
            'total_amount' => $this->faker->randomFloat(2, 500, 5000),
            'paid_amount' => 0,
            'currency' => 'USD',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivery_date_actual' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}