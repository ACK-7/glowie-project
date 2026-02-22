<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Customer;
use Faker\Factory as Faker;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get existing bookings and customers
        $bookings = Booking::with('customer')->get();
        $customers = Customer::all();
        
        if ($bookings->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('No bookings or customers found. Please run BookingsSeeder and CustomersSeeder first.');
            return;
        }
        
        $paymentMethods = ['bank_transfer', 'mobile_money', 'credit_card', 'cash'];
        $statuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];
        $currencies = ['USD', 'UGX', 'EUR', 'GBP'];
        
        // Create 50 sample payments
        for ($i = 0; $i < 50; $i++) {
            $booking = $bookings->random();
            $customer = $booking->customer ?? $customers->random();
            
            $amount = $faker->randomFloat(2, 500, 15000);
            $status = $faker->randomElement($statuses);
            $method = $faker->randomElement($paymentMethods);
            $currency = $faker->randomElement($currencies);
            
            // Adjust amount based on currency
            if ($currency === 'UGX') {
                $amount = $amount * 3700; // Convert to UGX
            }
            
            $payment = Payment::create([
                'payment_reference' => 'PAY-' . strtoupper($faker->bothify('??###??')),
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $method,
                'payment_gateway' => $this->getPaymentGateway($method),
                'transaction_id' => $faker->uuid(),
                'status' => $status,
                'payment_date' => $status === 'completed' ? $faker->dateTimeBetween('-6 months', 'now') : null,
                'notes' => $faker->optional(0.3)->sentence(),
                'metadata' => json_encode([
                    'gateway_response' => $faker->word(),
                    'fee_amount' => $faker->randomFloat(2, 10, 100),
                    'exchange_rate' => $currency !== 'USD' ? $faker->randomFloat(4, 0.5, 4000) : null,
                ]),
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
            ]);
            
            $this->command->info("Created payment: {$payment->payment_reference} - {$payment->status} - {$payment->currency} {$payment->amount}");
        }
        
        $this->command->info('Payments seeded successfully!');
    }
    
    private function getPaymentGateway(string $method): ?string
    {
        return match($method) {
            'bank_transfer' => 'bank_wire',
            'mobile_money' => 'mtn_mobile_money',
            'credit_card' => 'stripe',
            'cash' => null,
            default => null,
        };
    }
}