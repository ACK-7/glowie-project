<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_reference' => $this->generatePaymentReference(),
            'booking_id' => Booking::factory(),
            'customer_id' => Customer::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'USD',
            'payment_method' => $this->faker->randomElement(Payment::VALID_METHODS),
            'payment_gateway' => $this->faker->optional()->randomElement(['stripe', 'paypal', 'flutterwave']),
            'transaction_id' => $this->faker->optional()->uuid(),
            'status' => $this->faker->randomElement(Payment::VALID_STATUSES),
            'payment_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->randomElements([
                'gateway_fee' => $this->faker->randomFloat(2, 1, 50),
                'processing_time' => $this->faker->numberBetween(1, 300),
                'ip_address' => $this->faker->ipv4(),
            ]),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
            'payment_date' => null,
            'transaction_id' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_COMPLETED,
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'transaction_id' => $this->faker->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'payment_date' => null,
            'transaction_id' => null,
            'notes' => $this->faker->sentence(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_REFUNDED,
            'payment_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'transaction_id' => $this->faker->uuid(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_CANCELLED,
            'payment_date' => null,
            'transaction_id' => null,
            'notes' => $this->faker->sentence(),
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'payment_gateway' => null,
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_CREDIT_CARD,
            'payment_gateway' => 'stripe',
        ]);
    }

    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_MOBILE_MONEY,
            'payment_gateway' => 'flutterwave',
        ]);
    }

    private function generatePaymentReference(): string
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');
        $sequence = $this->faker->unique()->numberBetween(1, 999999);
        
        return $prefix . $year . $month . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}