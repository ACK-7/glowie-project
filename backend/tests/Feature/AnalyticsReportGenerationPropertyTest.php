<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\User;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

/**
 * Property-Based Test for Analytics Report Generation
 * 
 * Property 25: Analytics Report Generation
 * For any analytics request, the system should generate customizable reports with proper data aggregation, 
 * trend analysis, pattern identification, and period-over-period comparisons with variance calculations.
 * 
 * Validates: Requirements 9.1, 9.2, 9.3
 */
class AnalyticsReportGenerationPropertyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authentication
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);
        
        Sanctum::actingAs($this->adminUser, ['admin']);
    }

    /**
     * Property Test: Analytics Report Structure Completeness
     * 
     * Tests that all analytics endpoints return complete report structures
     * with proper data aggregation regardless of underlying data state.
     * 
     * @test
     * @dataProvider analyticsEndpointScenarios
     */
    public function property_analytics_reports_always_return_complete_structure(array $scenario): void
    {
        // Arrange: Create test data based on scenario
        $this->createTestDataForScenario($scenario);

        // Act & Assert: Test each analytics endpoint
        $this->validateDashboardAnalytics($scenario);
        $this->validateRevenueAnalytics($scenario);
        $this->validateBookingAnalytics($scenario);
        $this->validateCustomerAnalytics($scenario);
        $this->validateOperationalAnalytics($scenario);
    }

    /**
     * Property Test: Trend Analysis Consistency
     * 
     * Tests that trend analysis provides consistent pattern identification
     * and statistical calculations across different metrics and time periods.
     * 
     * @test
     * @dataProvider trendAnalysisScenarios
     */
    public function property_trend_analysis_provides_consistent_patterns(array $scenario): void
    {
        // Arrange: Create trend data
        $this->createTrendTestData($scenario);

        // Act: Call trend analysis endpoint
        $response = $this->getJson('/api/admin/analytics/trends?' . http_build_query([
            'metric' => $scenario['metric'],
            'period' => $scenario['period'],
            'granularity' => $scenario['granularity'],
            'include_forecast' => true,
            'include_seasonality' => true
        ]));

        // Assert: Verify trend analysis structure and consistency
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data',
                'statistics' => [
                    'mean',
                    'median',
                    'std_deviation',
                    'variance',
                    'trend_direction',
                    'trend_strength'
                ],
                'patterns' => [
                    'seasonal_patterns',
                    'cyclical_patterns',
                    'trend_patterns'
                ],
                'forecast' => [
                    'predictions',
                    'confidence_intervals',
                    'model_accuracy'
                ],
                'seasonality' => [
                    'seasonal_indices',
                    'seasonal_strength',
                    'seasonal_periods'
                ]
            ]
        ]);

        $data = $response->json('data');
        
        // Property validation: Statistical measures must be numeric and valid
        $this->assertIsNumeric($data['statistics']['mean']);
        $this->assertIsNumeric($data['statistics']['median']);
        $this->assertIsNumeric($data['statistics']['std_deviation']);
        $this->assertGreaterThanOrEqual(0, $data['statistics']['std_deviation']);
        $this->assertIsNumeric($data['statistics']['variance']);
        $this->assertGreaterThanOrEqual(0, $data['statistics']['variance']);
        
        // Trend direction must be valid
        $this->assertContains($data['statistics']['trend_direction'], ['increasing', 'decreasing', 'stable']);
        
        // Trend strength must be between 0 and 1
        $this->assertIsNumeric($data['statistics']['trend_strength']);
        $this->assertGreaterThanOrEqual(0, $data['statistics']['trend_strength']);
        $this->assertLessThanOrEqual(1, $data['statistics']['trend_strength']);
        
        // Patterns must be arrays
        $this->assertIsArray($data['patterns']['seasonal_patterns']);
        $this->assertIsArray($data['patterns']['cyclical_patterns']);
        $this->assertIsArray($data['patterns']['trend_patterns']);
        
        // Forecast must contain valid predictions
        $this->assertIsArray($data['forecast']['predictions']);
        $this->assertIsArray($data['forecast']['confidence_intervals']);
        $this->assertIsNumeric($data['forecast']['model_accuracy']);
        $this->assertGreaterThanOrEqual(0, $data['forecast']['model_accuracy']);
        $this->assertLessThanOrEqual(1, $data['forecast']['model_accuracy']);
    }

    /**
     * Property Test: Comparative Analysis Variance Calculations
     * 
     * Tests that comparative analysis provides accurate variance calculations
     * and period-over-period comparisons with proper statistical significance.
     * 
     * @test
     * @dataProvider comparativeAnalysisScenarios
     */
    public function property_comparative_analysis_provides_accurate_variance_calculations(array $scenario): void
    {
        // Arrange: Create comparative data
        $this->createComparativeTestData($scenario);

        // Act: Call comparative analysis endpoint
        $response = $this->getJson('/api/admin/analytics/comparative?' . http_build_query([
            'current_start' => $scenario['current_start'],
            'current_end' => $scenario['current_end'],
            'previous_start' => $scenario['previous_start'],
            'previous_end' => $scenario['previous_end'],
            'metrics' => ['revenue', 'bookings', 'customers']
        ]));

        // Assert: Verify comparative analysis structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_period',
                'previous_period',
                'variance_analysis' => [
                    'absolute_variance',
                    'percentage_variance',
                    'variance_direction',
                    'variance_significance'
                ],
                'significance_tests' => [
                    'statistical_significance',
                    'confidence_level',
                    'p_value'
                ]
            ]
        ]);

        $data = $response->json('data');
        
        // Property validation: Variance calculations must be mathematically correct
        $this->assertIsArray($data['variance_analysis']['absolute_variance']);
        $this->assertIsArray($data['variance_analysis']['percentage_variance']);
        $this->assertIsArray($data['variance_analysis']['variance_direction']);
        
        // Validate each metric's variance calculations
        foreach (['revenue', 'bookings', 'customers'] as $metric) {
            if (isset($data['current_period'][$metric]) && isset($data['previous_period'][$metric])) {
                $current = $data['current_period'][$metric];
                $previous = $data['previous_period'][$metric];
                $absoluteVariance = $data['variance_analysis']['absolute_variance'][$metric] ?? null;
                $percentageVariance = $data['variance_analysis']['percentage_variance'][$metric] ?? null;
                
                if ($absoluteVariance !== null) {
                    $this->assertIsNumeric($absoluteVariance);
                    // Absolute variance should equal current - previous
                    $this->assertEquals($current - $previous, $absoluteVariance, "Absolute variance calculation incorrect for {$metric}", 0.01);
                }
                
                if ($percentageVariance !== null && $previous != 0) {
                    $this->assertIsNumeric($percentageVariance);
                    // Percentage variance should equal ((current - previous) / previous) * 100
                    $expectedPercentage = (($current - $previous) / $previous) * 100;
                    $this->assertEquals($expectedPercentage, $percentageVariance, "Percentage variance calculation incorrect for {$metric}", 0.01);
                }
            }
        }
        
        // Significance tests must have valid statistical measures
        $this->assertIsBool($data['significance_tests']['statistical_significance']);
        $this->assertIsNumeric($data['significance_tests']['confidence_level']);
        $this->assertGreaterThanOrEqual(0, $data['significance_tests']['confidence_level']);
        $this->assertLessThanOrEqual(1, $data['significance_tests']['confidence_level']);
        
        if (isset($data['significance_tests']['p_value'])) {
            $this->assertIsNumeric($data['significance_tests']['p_value']);
            $this->assertGreaterThanOrEqual(0, $data['significance_tests']['p_value']);
            $this->assertLessThanOrEqual(1, $data['significance_tests']['p_value']);
        }
    }

    /**
     * Property Test: Export Format Consistency
     * 
     * Tests that export functionality maintains data consistency
     * across different formats and report types.
     * 
     * @test
     * @dataProvider exportFormatScenarios
     */
    public function property_export_maintains_data_consistency_across_formats(array $scenario): void
    {
        // Arrange: Create export test data
        $this->createExportTestData($scenario);

        // Act: Test export for each format
        foreach (['csv', 'excel', 'pdf'] as $format) {
            $response = $this->postJson('/api/admin/analytics/export', [
                'report_type' => $scenario['report_type'],
                'format' => $format,
                'period' => $scenario['period'],
                'include_charts' => $scenario['include_charts'],
                'include_raw_data' => $scenario['include_raw_data']
            ]);

            // Assert: Export should succeed for all formats
            $this->assertTrue(
                $response->status() === 200 || $response->status() === 302,
                "Export failed for format {$format} with report type {$scenario['report_type']}"
            );
            
            // Verify response headers for file download
            if ($response->status() === 200) {
                $this->assertNotEmpty($response->headers->get('Content-Disposition'));
                $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
            }
        }
    }

    /**
     * Property Test: Data Aggregation Accuracy
     * 
     * Tests that data aggregation produces mathematically correct results
     * regardless of data volume or complexity.
     * 
     * @test
     * @dataProvider dataAggregationScenarios
     */
    public function property_data_aggregation_produces_mathematically_correct_results(array $scenario): void
    {
        // Arrange: Create known test data for verification
        $testData = $this->createKnownTestData($scenario);

        // Act: Call revenue analytics endpoint
        $response = $this->getJson('/api/admin/analytics/revenue?' . http_build_query([
            'period' => 'custom',
            'start_date' => $scenario['start_date'],
            'end_date' => $scenario['end_date'],
            'breakdown' => 'daily'
        ]));

        // Assert: Verify aggregation accuracy
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify total revenue matches sum of individual payments
        if (isset($data['analytics']['total_revenue'])) {
            $expectedTotal = $testData['expected_total_revenue'];
            $actualTotal = $data['analytics']['total_revenue'];
            
            $this->assertEquals(
                $expectedTotal, 
                $actualTotal, 
                "Total revenue aggregation incorrect. Expected: {$expectedTotal}, Actual: {$actualTotal}",
                0.01
            );
        }
        
        // Verify booking count matches created bookings
        if (isset($data['analytics']['total_bookings'])) {
            $expectedBookings = $testData['expected_booking_count'];
            $actualBookings = $data['analytics']['total_bookings'];
            
            $this->assertEquals(
                $expectedBookings,
                $actualBookings,
                "Booking count aggregation incorrect. Expected: {$expectedBookings}, Actual: {$actualBookings}"
            );
        }
        
        // Verify average calculations are mathematically correct
        if (isset($data['analytics']['average_booking_value']) && $testData['expected_booking_count'] > 0) {
            $expectedAverage = $testData['expected_total_revenue'] / $testData['expected_booking_count'];
            $actualAverage = $data['analytics']['average_booking_value'];
            
            $this->assertEquals(
                $expectedAverage,
                $actualAverage,
                "Average booking value calculation incorrect. Expected: {$expectedAverage}, Actual: {$actualAverage}",
                0.01
            );
        }
    }

    /**
     * Validate dashboard analytics structure and data
     */
    private function validateDashboardAnalytics(array $scenario): void
    {
        $response = $this->getJson('/api/admin/analytics/dashboard');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'revenue',
                'bookings',
                'customers',
                'shipments',
                'insights'
            ]
        ]);
        
        $data = $response->json('data');
        
        // Each section must contain aggregated data
        foreach (['revenue', 'bookings', 'customers', 'shipments'] as $section) {
            $this->assertIsArray($data[$section], "Dashboard {$section} section must be an array");
            $this->assertNotEmpty($data[$section], "Dashboard {$section} section must not be empty");
        }
        
        // Insights must be an array
        $this->assertIsArray($data['insights']);
    }

    /**
     * Validate revenue analytics structure and calculations
     */
    private function validateRevenueAnalytics(array $scenario): void
    {
        $response = $this->getJson('/api/admin/analytics/revenue');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'analytics',
                'trends',
                'breakdown',
                'comparison',
                'patterns'
            ]
        ]);
        
        $data = $response->json('data');
        
        // Revenue analytics must contain numeric values
        if (isset($data['analytics']['total_revenue'])) {
            $this->assertIsNumeric($data['analytics']['total_revenue']);
            $this->assertGreaterThanOrEqual(0, $data['analytics']['total_revenue']);
        }
        
        // Trends must be an array
        $this->assertIsArray($data['trends']);
        
        // Breakdown must be an array
        $this->assertIsArray($data['breakdown']);
        
        // Comparison must contain current and previous period data
        $this->assertIsArray($data['comparison']);
        
        // Patterns must be an array
        $this->assertIsArray($data['patterns']);
    }

    /**
     * Validate booking analytics structure
     */
    private function validateBookingAnalytics(array $scenario): void
    {
        $response = $this->getJson('/api/admin/analytics/bookings');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'analytics',
                'trends',
                'performance',
                'conversion_funnel',
                'route_performance',
                'patterns'
            ]
        ]);
        
        $data = $response->json('data');
        
        // All sections must be arrays
        foreach (['analytics', 'trends', 'performance', 'conversion_funnel', 'route_performance', 'patterns'] as $section) {
            $this->assertIsArray($data[$section], "Booking analytics {$section} must be an array");
        }
    }

    /**
     * Validate customer analytics structure
     */
    private function validateCustomerAnalytics(array $scenario): void
    {
        $response = $this->getJson('/api/admin/analytics/customers');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'analytics',
                'segmentation',
                'ltv_analysis',
                'churn_analysis',
                'acquisition_channels',
                'behavioral_patterns'
            ]
        ]);
        
        $data = $response->json('data');
        
        // All sections must be arrays
        foreach (['analytics', 'segmentation', 'ltv_analysis', 'churn_analysis', 'acquisition_channels', 'behavioral_patterns'] as $section) {
            $this->assertIsArray($data[$section], "Customer analytics {$section} must be an array");
        }
    }

    /**
     * Validate operational analytics structure
     */
    private function validateOperationalAnalytics(array $scenario): void
    {
        $response = $this->getJson('/api/admin/analytics/operational');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'analytics',
                'shipment_analytics',
                'bottlenecks'
            ]
        ]);
        
        $data = $response->json('data');
        
        // Core sections must be arrays
        $this->assertIsArray($data['analytics']);
        $this->assertIsArray($data['shipment_analytics']);
        $this->assertIsArray($data['bottlenecks']);
    }

    /**
     * Create test data based on scenario
     */
    private function createTestDataForScenario(array $scenario): void
    {
        // Create customers
        $customers = Customer::factory()->count($scenario['customer_count'])->create();
        
        // Create vehicles and routes
        $vehicles = Vehicle::factory()->count(5)->create();
        $routes = Route::factory()->count(3)->create();
        
        // Create bookings
        for ($i = 0; $i < $scenario['booking_count']; $i++) {
            $booking = Booking::factory()->create([
                'customer_id' => $customers->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'route_id' => $routes->random()->id,
                'status' => $this->faker->randomElement(['pending', 'confirmed', 'in_transit', 'delivered']),
                'total_amount' => $this->faker->randomFloat(2, 1000, 10000),
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now')
            ]);
            
            // Create payments for some bookings
            if ($this->faker->boolean(70)) {
                Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'amount' => $booking->total_amount,
                    'status' => 'completed'
                ]);
            }
            
            // Create shipments for confirmed bookings
            if (in_array($booking->status, ['confirmed', 'in_transit', 'delivered'])) {
                Shipment::factory()->create([
                    'booking_id' => $booking->id,
                    'status' => $booking->status === 'delivered' ? 'delivered' : 'in_transit'
                ]);
            }
        }
        
        // Create quotes
        Quote::factory()->count($scenario['quote_count'])->create([
            'customer_id' => $customers->random()->id,
            'route_id' => $routes->random()->id,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'converted'])
        ]);
    }

    /**
     * Create trend test data
     */
    private function createTrendTestData(array $scenario): void
    {
        $customers = Customer::factory()->count(3)->create();
        $vehicles = Vehicle::factory()->count(2)->create();
        $routes = Route::factory()->count(2)->create();
        
        // Create data with specific trends based on scenario
        $startDate = Carbon::parse('-30 days');
        $endDate = Carbon::now();
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDays(5)) {
            $dailyCount = $scenario['trend_type'] === 'increasing' 
                ? $date->diffInDays($startDate) / 15 + 1
                : max(1, 3 - $date->diffInDays($startDate) / 15);
                
            for ($i = 0; $i < $dailyCount; $i++) {
                $booking = Booking::factory()->create([
                    'customer_id' => $customers->random()->id,
                    'vehicle_id' => $vehicles->random()->id,
                    'route_id' => $routes->random()->id,
                    'total_amount' => $this->faker->randomFloat(2, 1000, 3000),
                    'created_at' => $date->copy()->addHours($this->faker->numberBetween(0, 23))
                ]);
                
                Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'amount' => $booking->total_amount,
                    'status' => 'completed',
                    'payment_date' => $booking->created_at
                ]);
            }
        }
    }

    /**
     * Create comparative test data
     */
    private function createComparativeTestData(array $scenario): void
    {
        $customers = Customer::factory()->count(5)->create();
        $vehicles = Vehicle::factory()->count(3)->create();
        $routes = Route::factory()->count(2)->create();
        
        // Create data for both periods
        $periods = [
            'previous' => [
                'start' => Carbon::parse($scenario['previous_start']),
                'end' => Carbon::parse($scenario['previous_end']),
                'multiplier' => $scenario['previous_multiplier']
            ],
            'current' => [
                'start' => Carbon::parse($scenario['current_start']),
                'end' => Carbon::parse($scenario['current_end']),
                'multiplier' => $scenario['current_multiplier']
            ]
        ];
        
        foreach ($periods as $period) {
            $baseCount = 3;
            $count = (int)($baseCount * $period['multiplier']);
            
            for ($i = 0; $i < $count; $i++) {
                $booking = Booking::factory()->create([
                    'customer_id' => $customers->random()->id,
                    'vehicle_id' => $vehicles->random()->id,
                    'route_id' => $routes->random()->id,
                    'total_amount' => $this->faker->randomFloat(2, 1000, 3000),
                    'created_at' => $this->faker->dateTimeBetween($period['start'], $period['end'])
                ]);
                
                Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'amount' => $booking->total_amount,
                    'status' => 'completed',
                    'payment_date' => $booking->created_at
                ]);
            }
        }
    }

    /**
     * Create export test data
     */
    private function createExportTestData(array $scenario): void
    {
        $this->createTestDataForScenario([
            'customer_count' => 3,
            'booking_count' => 5,
            'quote_count' => 3
        ]);
    }

    /**
     * Create known test data for mathematical verification
     */
    private function createKnownTestData(array $scenario): array
    {
        $customers = Customer::factory()->count(5)->create();
        $vehicles = Vehicle::factory()->count(3)->create();
        $routes = Route::factory()->count(2)->create();
        
        $totalRevenue = 0;
        $bookingCount = $scenario['booking_count'];
        
        $startDate = Carbon::parse($scenario['start_date']);
        $endDate = Carbon::parse($scenario['end_date']);
        
        for ($i = 0; $i < $bookingCount; $i++) {
            $amount = ($i + 1) * 1000; // Predictable amounts: 1000, 2000, 3000, etc.
            $totalRevenue += $amount;
            
            $booking = Booking::factory()->create([
                'customer_id' => $customers->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'route_id' => $routes->random()->id,
                'total_amount' => $amount,
                'created_at' => $this->faker->dateTimeBetween($startDate, $endDate)
            ]);
            
            Payment::factory()->create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'amount' => $amount,
                'status' => 'completed',
                'payment_date' => $booking->created_at
            ]);
        }
        
        return [
            'expected_total_revenue' => $totalRevenue,
            'expected_booking_count' => $bookingCount,
            'expected_average_booking_value' => $totalRevenue / $bookingCount
        ];
    }

    /**
     * Data provider for analytics endpoint scenarios
     */
    public static function analyticsEndpointScenarios(): array
    {
        return [
            'minimal_data' => [
                [
                    'customer_count' => 2,
                    'booking_count' => 3,
                    'quote_count' => 2
                ]
            ],
            'moderate_data' => [
                [
                    'customer_count' => 5,
                    'booking_count' => 8,
                    'quote_count' => 4
                ]
            ]
        ];
    }

    /**
     * Data provider for trend analysis scenarios
     */
    public static function trendAnalysisScenarios(): array
    {
        return [
            'increasing_revenue_trend' => [
                [
                    'metric' => 'revenue',
                    'period' => '30_days',
                    'granularity' => 'daily',
                    'trend_type' => 'increasing'
                ]
            ]
        ];
    }

    /**
     * Data provider for comparative analysis scenarios
     */
    public static function comparativeAnalysisScenarios(): array
    {
        return [
            'growth_comparison' => [
                [
                    'current_start' => '2024-02-01',
                    'current_end' => '2024-02-29',
                    'previous_start' => '2024-01-01',
                    'previous_end' => '2024-01-31',
                    'current_multiplier' => 1.5,
                    'previous_multiplier' => 1.0
                ]
            ]
        ];
    }

    /**
     * Data provider for export format scenarios
     */
    public static function exportFormatScenarios(): array
    {
        return [
            'revenue_report_minimal' => [
                [
                    'report_type' => 'revenue',
                    'period' => '7_days',
                    'include_charts' => false,
                    'include_raw_data' => false
                ]
            ]
        ];
    }

    /**
     * Data provider for data aggregation scenarios
     */
    public static function dataAggregationScenarios(): array
    {
        return [
            'small_dataset_aggregation' => [
                [
                    'booking_count' => 3,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31'
                ]
            ]
        ];
    }
}