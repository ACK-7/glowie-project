<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Property-Based Test for Recent Activity Display Accuracy
 * 
 * Property 3: Recent Activity Display Accuracy
 * For any recent activity request, the response should contain exactly 10 items each of 
 * bookings, quotes, and shipment updates, all with valid timestamps, sorted in descending 
 * chronological order.
 * 
 * Validates: Requirements 1.4
 */
class RecentActivityDisplayPropertyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force SQLite configuration before any database operations
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        
        // Clear any existing database connections
        DB::purge();
        
        // Run migrations
        $this->artisan('migrate:fresh');
        
        // Create admin user for authentication
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);
        
        // Use Sanctum's actingAs with admin abilities
        Sanctum::actingAs($this->adminUser, ['admin']);
    }

    /**
     * Property Test: Recent Activity Response Structure and Limits
     * 
     * Tests that recent activity endpoint always returns the correct structure
     * with proper limits regardless of the underlying data volume.
     * 
     * @test
     * @dataProvider recentActivityDataScenarios
     */
    public function property_recent_activity_returns_correct_structure_and_limits(array $scenario): void
    {
        // Arrange: Create test data based on scenario
        $this->createTestDataForScenario($scenario);

        // Act: Call recent activity endpoint
        $response = $this->getJson('/api/admin/dashboard/recent-activity');

        // Assert: Verify response structure and completeness
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'recent_bookings',
                'recent_quotes',
                'recent_shipments',
                'recent_payments',
                'system_alerts'
            ]
        ]);

        $data = $response->json('data');
        
        // Property validation: Each activity type should have at most 10 items
        $this->assertLessThanOrEqual(10, count($data['recent_bookings']));
        $this->assertLessThanOrEqual(10, count($data['recent_quotes']));
        $this->assertLessThanOrEqual(10, count($data['recent_shipments']));
        $this->assertLessThanOrEqual(10, count($data['recent_payments']));
        $this->assertLessThanOrEqual(10, count($data['system_alerts']));

        // If we have more than 10 items in database, should return exactly 10
        if ($scenario['total_bookings'] >= 10) {
            $this->assertCount(10, $data['recent_bookings']);
        } else {
            $this->assertCount($scenario['total_bookings'], $data['recent_bookings']);
        }

        if ($scenario['total_quotes'] >= 10) {
            $this->assertCount(10, $data['recent_quotes']);
        } else {
            $this->assertCount($scenario['total_quotes'], $data['recent_quotes']);
        }

        if ($scenario['total_shipments'] >= 10) {
            $this->assertCount(10, $data['recent_shipments']);
        } else {
            $this->assertCount($scenario['total_shipments'], $data['recent_shipments']);
        }
    }

    /**
     * Property Test: Recent Activity Timestamp Validity and Sorting
     * 
     * Tests that all returned items have valid timestamps and are sorted
     * in descending chronological order.
     * 
     * @test
     * @dataProvider recentActivityDataScenarios
     */
    public function property_recent_activity_items_have_valid_timestamps_and_correct_sorting(array $scenario): void
    {
        // Arrange: Create test data with specific timestamps
        $this->createTestDataWithTimestamps($scenario);

        // Act: Call recent activity endpoint
        $response = $this->getJson('/api/admin/dashboard/recent-activity');

        // Assert: Verify response success
        $response->assertStatus(200);
        $data = $response->json('data');

        // Property validation: All bookings have valid timestamps and are sorted
        $this->validateTimestampsAndSorting($data['recent_bookings'], 'created_at');
        
        // Property validation: All quotes have valid timestamps and are sorted
        $this->validateTimestampsAndSorting($data['recent_quotes'], 'created_at');
        
        // Property validation: All shipments have valid timestamps and are sorted (by updated_at)
        $this->validateTimestampsAndSorting($data['recent_shipments'], 'updated_at');
        
        // Property validation: All payments have valid timestamps and are sorted
        $this->validateTimestampsAndSorting($data['recent_payments'], 'created_at');
    }

    /**
     * Property Test: Recent Activity Item Structure Completeness
     * 
     * Tests that each returned item contains all required fields with proper data types.
     * 
     * @test
     */
    public function property_recent_activity_items_have_complete_structure(): void
    {
        // Arrange: Create minimal test data
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $route = Route::factory()->create();
        
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'route_id' => $route->id,
        ]);
        
        $quote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'route_id' => $route->id,
        ]);
        
        $shipment = Shipment::factory()->create([
            'booking_id' => $booking->id,
        ]);
        
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
        ]);

        // Act: Call recent activity endpoint
        $response = $this->getJson('/api/admin/dashboard/recent-activity');

        // Assert: Verify response success
        $response->assertStatus(200);
        $data = $response->json('data');

        // Property validation: Booking structure completeness
        if (!empty($data['recent_bookings'])) {
            $bookingItem = $data['recent_bookings'][0];
            $this->assertArrayHasKey('id', $bookingItem);
            $this->assertArrayHasKey('reference', $bookingItem);
            $this->assertArrayHasKey('customer_name', $bookingItem);
            $this->assertArrayHasKey('customer_email', $bookingItem);
            $this->assertArrayHasKey('vehicle', $bookingItem);
            $this->assertArrayHasKey('route', $bookingItem);
            $this->assertArrayHasKey('status', $bookingItem);
            $this->assertArrayHasKey('status_label', $bookingItem);
            $this->assertArrayHasKey('amount', $bookingItem);
            $this->assertArrayHasKey('currency', $bookingItem);
            $this->assertArrayHasKey('created_at', $bookingItem);
            $this->assertArrayHasKey('created_at_human', $bookingItem);
            
            // Validate data types
            $this->assertIsInt($bookingItem['id']);
            $this->assertIsString($bookingItem['reference']);
            $this->assertIsString($bookingItem['customer_name']);
            $this->assertIsString($bookingItem['status']);
            $this->assertIsNumeric($bookingItem['amount']);
            $this->assertIsString($bookingItem['created_at']);
        }

        // Property validation: Quote structure completeness
        if (!empty($data['recent_quotes'])) {
            $quoteItem = $data['recent_quotes'][0];
            $this->assertArrayHasKey('id', $quoteItem);
            $this->assertArrayHasKey('reference', $quoteItem);
            $this->assertArrayHasKey('customer_name', $quoteItem);
            $this->assertArrayHasKey('customer_email', $quoteItem);
            $this->assertArrayHasKey('route', $quoteItem);
            $this->assertArrayHasKey('status', $quoteItem);
            $this->assertArrayHasKey('status_label', $quoteItem);
            $this->assertArrayHasKey('amount', $quoteItem);
            $this->assertArrayHasKey('currency', $quoteItem);
            $this->assertArrayHasKey('valid_until', $quoteItem);
            $this->assertArrayHasKey('is_expired', $quoteItem);
            $this->assertArrayHasKey('created_at', $quoteItem);
            $this->assertArrayHasKey('created_at_human', $quoteItem);
            
            // Validate data types
            $this->assertIsInt($quoteItem['id']);
            $this->assertIsString($quoteItem['reference']);
            $this->assertIsString($quoteItem['customer_name']);
            $this->assertIsString($quoteItem['status']);
            $this->assertIsNumeric($quoteItem['amount']);
            $this->assertIsBool($quoteItem['is_expired']);
            $this->assertIsString($quoteItem['created_at']);
        }

        // Property validation: Shipment structure completeness
        if (!empty($data['recent_shipments'])) {
            $shipmentItem = $data['recent_shipments'][0];
            $this->assertArrayHasKey('id', $shipmentItem);
            $this->assertArrayHasKey('tracking_number', $shipmentItem);
            $this->assertArrayHasKey('customer_name', $shipmentItem);
            $this->assertArrayHasKey('current_location', $shipmentItem);
            $this->assertArrayHasKey('status', $shipmentItem);
            $this->assertArrayHasKey('status_label', $shipmentItem);
            $this->assertArrayHasKey('progress_percentage', $shipmentItem);
            $this->assertArrayHasKey('estimated_arrival', $shipmentItem);
            $this->assertArrayHasKey('is_delayed', $shipmentItem);
            $this->assertArrayHasKey('updated_at', $shipmentItem);
            $this->assertArrayHasKey('updated_at_human', $shipmentItem);
            
            // Validate data types
            $this->assertIsInt($shipmentItem['id']);
            $this->assertIsString($shipmentItem['tracking_number']);
            $this->assertIsString($shipmentItem['customer_name']);
            $this->assertIsString($shipmentItem['status']);
            $this->assertIsNumeric($shipmentItem['progress_percentage']);
            $this->assertIsBool($shipmentItem['is_delayed']);
            $this->assertIsString($shipmentItem['updated_at']);
        }

        // Property validation: Payment structure completeness
        if (!empty($data['recent_payments'])) {
            $paymentItem = $data['recent_payments'][0];
            $this->assertArrayHasKey('id', $paymentItem);
            $this->assertArrayHasKey('reference', $paymentItem);
            $this->assertArrayHasKey('customer_name', $paymentItem);
            $this->assertArrayHasKey('amount', $paymentItem);
            $this->assertArrayHasKey('currency', $paymentItem);
            $this->assertArrayHasKey('method', $paymentItem);
            $this->assertArrayHasKey('method_label', $paymentItem);
            $this->assertArrayHasKey('status', $paymentItem);
            $this->assertArrayHasKey('status_label', $paymentItem);
            $this->assertArrayHasKey('payment_date', $paymentItem);
            $this->assertArrayHasKey('created_at', $paymentItem);
            $this->assertArrayHasKey('created_at_human', $paymentItem);
            
            // Validate data types
            $this->assertIsInt($paymentItem['id']);
            $this->assertIsString($paymentItem['reference']);
            $this->assertIsString($paymentItem['customer_name']);
            $this->assertIsNumeric($paymentItem['amount']);
            $this->assertIsString($paymentItem['method']);
            $this->assertIsString($paymentItem['status']);
            $this->assertIsString($paymentItem['created_at']);
        }
    }

    /**
     * Property Test: Recent Activity Filtering by Type
     * 
     * Tests that when a specific type filter is applied, only that type is returned.
     * 
     * @test
     */
    public function property_recent_activity_filtering_by_type_works_correctly(): void
    {
        // Arrange: Create test data
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $route = Route::factory()->create();
        
        Booking::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'route_id' => $route->id,
        ]);
        
        Quote::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'route_id' => $route->id,
        ]);

        // Act & Assert: Test filtering by bookings
        $response = $this->getJson('/api/admin/dashboard/recent-activity?type=bookings');
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should only contain recent_bookings
        $this->assertArrayHasKey('recent_bookings', $data);
        $this->assertArrayNotHasKey('recent_quotes', $data);
        $this->assertArrayNotHasKey('recent_shipments', $data);
        $this->assertArrayNotHasKey('recent_payments', $data);
        $this->assertArrayNotHasKey('system_alerts', $data);

        // Act & Assert: Test filtering by quotes
        $response = $this->getJson('/api/admin/dashboard/recent-activity?type=quotes');
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should only contain recent_quotes
        $this->assertArrayHasKey('recent_quotes', $data);
        $this->assertArrayNotHasKey('recent_bookings', $data);
        $this->assertArrayNotHasKey('recent_shipments', $data);
        $this->assertArrayNotHasKey('recent_payments', $data);
        $this->assertArrayNotHasKey('system_alerts', $data);
    }

    /**
     * Helper method to validate timestamps and sorting
     */
    private function validateTimestampsAndSorting(array $items, string $timestampField): void
    {
        $previousTimestamp = null;
        
        foreach ($items as $item) {
            // Validate timestamp exists and is valid ISO format
            $this->assertArrayHasKey($timestampField, $item);
            $this->assertNotEmpty($item[$timestampField]);
            
            // Validate timestamp is valid ISO 8601 format
            $timestamp = Carbon::parse($item[$timestampField]);
            $this->assertInstanceOf(Carbon::class, $timestamp);
            
            // Validate descending chronological order
            if ($previousTimestamp !== null) {
                $this->assertGreaterThanOrEqual(
                    $timestamp->timestamp,
                    $previousTimestamp->timestamp,
                    "Items should be sorted in descending chronological order"
                );
            }
            
            $previousTimestamp = $timestamp;
        }
    }

    /**
     * Helper method to create test data for scenarios
     */
    private function createTestDataForScenario(array $scenario): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $route = Route::factory()->create();

        // Create bookings
        if ($scenario['total_bookings'] > 0) {
            Booking::factory()->count($scenario['total_bookings'])->create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
            ]);
        }

        // Create quotes
        if ($scenario['total_quotes'] > 0) {
            Quote::factory()->count($scenario['total_quotes'])->create([
                'customer_id' => $customer->id,
                'route_id' => $route->id,
            ]);
        }

        // Create shipments (need bookings first)
        if ($scenario['total_shipments'] > 0) {
            $bookings = Booking::factory()->count($scenario['total_shipments'])->create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
            ]);
            
            foreach ($bookings as $booking) {
                Shipment::factory()->create([
                    'booking_id' => $booking->id,
                ]);
            }
        }
    }

    /**
     * Helper method to create test data with specific timestamps for sorting tests
     */
    private function createTestDataWithTimestamps(array $scenario): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $route = Route::factory()->create();

        // Create bookings with descending timestamps
        for ($i = 0; $i < min($scenario['total_bookings'], 5); $i++) {
            Booking::factory()->create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
                'created_at' => now()->subMinutes($i * 10),
                'updated_at' => now()->subMinutes($i * 10),
            ]);
        }

        // Create quotes with descending timestamps
        for ($i = 0; $i < min($scenario['total_quotes'], 5); $i++) {
            Quote::factory()->create([
                'customer_id' => $customer->id,
                'route_id' => $route->id,
                'created_at' => now()->subMinutes($i * 15),
                'updated_at' => now()->subMinutes($i * 15),
            ]);
        }

        // Create shipments with descending update timestamps
        for ($i = 0; $i < min($scenario['total_shipments'], 5); $i++) {
            $booking = Booking::factory()->create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
            ]);
            
            Shipment::factory()->create([
                'booking_id' => $booking->id,
                'created_at' => now()->subMinutes($i * 20),
                'updated_at' => now()->subMinutes($i * 5), // More recent updates
            ]);
        }
    }

    /**
     * Data provider for recent activity scenarios
     * Generates various data volumes to test property behavior
     */
    public static function recentActivityDataScenarios(): array
    {
        return [
            'empty_database' => [
                [
                    'total_bookings' => 0,
                    'total_quotes' => 0,
                    'total_shipments' => 0,
                ]
            ],
            'minimal_data' => [
                [
                    'total_bookings' => 3,
                    'total_quotes' => 2,
                    'total_shipments' => 1,
                ]
            ],
            'more_than_ten_items' => [
                [
                    'total_bookings' => 15,
                    'total_quotes' => 12,
                    'total_shipments' => 8,
                ]
            ]
        ];
    }
}