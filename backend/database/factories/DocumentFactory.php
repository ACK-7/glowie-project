<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'customer_id' => Customer::factory(),
            'document_type' => $this->faker->randomElement(Document::VALID_TYPES),
            'file_name' => $this->faker->word() . '.pdf',
            'file_path' => 'documents/' . $this->faker->year() . '/' . $this->faker->month() . '/' . $this->faker->randomNumber(3) . '/' . $this->faker->word() . '.pdf',
            'file_size' => $this->faker->numberBetween(1024, 5242880), // 1KB to 5MB
            'mime_type' => $this->faker->randomElement(Document::ALLOWED_MIME_TYPES),
            'status' => $this->faker->randomElement(Document::VALID_STATUSES),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Document::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Document::STATUS_APPROVED,
            'verified_by' => 1,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Document::STATUS_REJECTED,
            'verified_by' => 1,
            'verified_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Document::STATUS_EXPIRED,
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function passport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => Document::TYPE_PASSPORT,
            'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+10 years'),
        ]);
    }

    public function license(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => Document::TYPE_LICENSE,
            'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+5 years'),
        ]);
    }

    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => Document::TYPE_INVOICE,
            'expiry_date' => null,
        ]);
    }
}