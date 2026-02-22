<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

class SeedRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:seed-routes {--fresh : Clear existing routes first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed shipping routes for the quotation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting shipping routes seeding...');

        // Clear existing routes if --fresh flag is set
        if ($this->option('fresh')) {
            $this->warn('⚠️  Clearing existing routes...');
            
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Delete all routes
            $deleted = DB::table('routes')->delete();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->info("✓ Cleared {$deleted} existing route(s)");
        }

        // Shipping routes matching common quotation origins
        $routes = [
            // Japan to Uganda Routes
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Tokyo',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 45,
                'base_price' => 2500.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Yokohama',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 42,
                'base_price' => 2400.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'Japan',
                'origin_city' => 'Osaka',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 48,
                'base_price' => 2600.00,
                'is_active' => true,
            ],
            
            // UK to Uganda Routes
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'London',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 35,
                'base_price' => 2200.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'Southampton',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 32,
                'base_price' => 2100.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'United Kingdom',
                'origin_city' => 'Liverpool',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 38,
                'base_price' => 2300.00,
                'is_active' => true,
            ],
            
            // UAE to Uganda Routes
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Dubai',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 21,
                'base_price' => 1800.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Abu Dhabi',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 25,
                'base_price' => 1900.00,
                'is_active' => true,
            ],
            [
                'origin_country' => 'UAE',
                'origin_city' => 'Sharjah',
                'destination_country' => 'Uganda',
                'destination_city' => 'Kampala',
                'estimated_days' => 23,
                'base_price' => 1850.00,
                'is_active' => true,
            ],
        ];

        $this->info('📝 Creating shipping routes...');

        $created = 0;
        $updated = 0;

        foreach ($routes as $routeData) {
            $route = Route::updateOrCreate(
                [
                    'origin_country' => $routeData['origin_country'],
                    'origin_city' => $routeData['origin_city'],
                    'destination_country' => $routeData['destination_country'],
                    'destination_city' => $routeData['destination_city']
                ],
                $routeData
            );

            if ($route->wasRecentlyCreated) {
                $created++;
                $this->info("  ✓ Created: {$routeData['origin_city']}, {$routeData['origin_country']} → {$routeData['destination_city']}, {$routeData['destination_country']}");
            } else {
                $updated++;
                $this->line("  ↻ Updated: {$routeData['origin_city']}, {$routeData['origin_country']} → {$routeData['destination_city']}, {$routeData['destination_country']}");
            }
        }

        $this->newLine();
        $this->info("✅ Shipping routes seeded successfully!");
        $this->info("   Created: {$created} routes");
        $this->info("   Updated: {$updated} routes");
        $this->newLine();
        
        // Display summary table
        $this->table(
            ['Origin', 'Destination', 'Base Price', 'Days'],
            collect($routes)->map(function ($route) {
                return [
                    "{$route['origin_city']}, {$route['origin_country']}",
                    "{$route['destination_city']}, {$route['destination_country']}",
                    '$' . number_format($route['base_price'], 2),
                    $route['estimated_days'] . ' days'
                ];
            })->toArray()
        );
        
        $this->newLine();
        $this->info('🎉 Routes are now available for quotations!');

        return Command::SUCCESS;
    }
}
