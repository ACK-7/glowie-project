<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestQuoteWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:quote-workflow {--count=1 : Number of quotes to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the quote creation workflow end-to-end';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $this->info("🧪 Testing Quote Workflow - Creating {$count} quote(s)...");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Test #{$i}:");
            
            try {
                DB::beginTransaction();

                // Step 1: Check/Create Customer
                $this->line("  [1/5] Creating/Checking customer...");
                $customer = Customer::firstOrCreate(
                    ['email' => 'test' . $i . '@example.com'],
                    [
                        'first_name' => 'Test',
                        'last_name' => "User {$i}",
                        'phone' => '+256-700-000-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                        'password' => bcrypt('password123'),
                        'is_active' => true,
                        'is_verified' => false,
                    ]
                );
                $this->info("      ✓ Customer: {$customer->email} (ID: {$customer->id})");

                // Step 2: Get Route
                $this->line("  [2/5] Finding shipping route...");
                $route = Route::where('origin_country', 'Japan')
                            ->where('destination_country', 'Uganda')
                            ->where('is_active', true)
                            ->first();
                
                if (!$route) {
                    throw new \Exception("No active route found from Japan to Uganda. Please seed routes first.");
                }
                $this->info("      ✓ Route: {$route->origin_city}, {$route->origin_country} → {$route->destination_city}, {$route->destination_country} (ID: {$route->id})");

                // Step 3: Create Vehicle
                $this->line("  [3/5] Creating vehicle...");
                $vehicle = Vehicle::create([
                    'vehicle_type_id' => 1,
                    'make' => 'Toyota',
                    'model' => 'Test Model ' . $i,
                    'year' => 2020 + ($i % 5),
                    'description' => 'Test vehicle for quote workflow',
                    'is_running' => true,
                ]);
                $this->info("      ✓ Vehicle: {$vehicle->year} {$vehicle->make} {$vehicle->model} (ID: {$vehicle->id})");

                // Step 4: Calculate Price
                $this->line("  [4/5] Calculating quote price...");
                $shippingCost = $route->base_price;
                $customsDuty = 800;
                $vat = ($shippingCost + $customsDuty) * 0.18;
                $levies = 350;
                $total = $shippingCost + $customsDuty + $vat + $levies;
                $this->info("      ✓ Base Price: $" . number_format($shippingCost, 2));
                $this->info("      ✓ Total Amount: $" . number_format($total, 2));

                // Step 5: Create Quote
                $this->line("  [5/5] Creating quote...");
                $quote = Quote::create([
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_details' => [
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'vehicle_type_id' => $vehicle->vehicle_type_id,
                    ],
                    'route_id' => $route->id,
                    'base_price' => $shippingCost,
                    'additional_fees' => [
                        ['name' => 'Customs Duty', 'amount' => $customsDuty],
                        ['name' => 'VAT', 'amount' => $vat],
                        ['name' => 'Levies', 'amount' => $levies]
                    ],
                    'total_amount' => $total,
                    'status' => 'pending',
                    'valid_until' => now()->addDays(30),
                ]);

                DB::commit();

                $this->info("      ✓ Quote created successfully!");
                $this->line("         Reference: {$quote->quote_reference}");
                $this->line("         Quote ID: {$quote->id}");
                $this->line("         Status: {$quote->status}");
                $this->line("         Valid Until: {$quote->valid_until->format('Y-m-d')}");
                $successCount++;
                $this->newLine();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("      ✗ Failed: " . $e->getMessage());
                $errors[] = [
                    'test' => $i,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
                $failCount++;
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info("📊 Test Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tests', $count],
                ['✅ Successful', $successCount],
                ['❌ Failed', $failCount],
            ]
        );

        if ($failCount > 0) {
            $this->newLine();
            $this->error("❌ Some tests failed. Errors:");
            foreach ($errors as $error) {
                $this->error("  Test #{$error['test']}: {$error['error']}");
            }
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✅ All tests passed! Quote workflow is working correctly.");
        return Command::SUCCESS;
    }
}
