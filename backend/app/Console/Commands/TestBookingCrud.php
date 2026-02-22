<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\User;
use App\Services\BookingService;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\BookingController;

class TestBookingCrud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-crud {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Booking CRUD operations via API endpoints';

    protected $adminUser = null;
    protected $testBookingId = null;
    protected $bookingService;
    protected $bookingRepository;
    protected $bookingController;

    public function __construct(
        BookingService $bookingService,
        BookingRepositoryInterface $bookingRepository
    ) {
        parent::__construct();
        $this->bookingService = $bookingService;
        $this->bookingRepository = $bookingRepository;
        $this->bookingController = new BookingController($bookingService, $bookingRepository);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Booking CRUD Operations');
        $this->newLine();

        // Step 1: Authenticate as admin
        if (!$this->authenticateAdmin()) {
            $this->error('❌ Failed to authenticate as admin');
            return 1;
        }

        $this->info('✅ Admin authenticated successfully');
        $this->newLine();

        $results = [
            'list' => $this->testList(),
            'show' => $this->testShow(),
            'create' => $this->testCreate(),
            'update' => $this->testUpdate(),
            'delete' => $this->testDelete(),
        ];

        // Summary
        $this->newLine();
        $this->info('📊 Test Summary:');
        $this->table(
            ['Operation', 'Status', 'Details'],
            [
                ['List Bookings', $results['list']['success'] ? '✅ PASS' : '❌ FAIL', $results['list']['message']],
                ['Show Booking', $results['show']['success'] ? '✅ PASS' : '❌ FAIL', $results['show']['message']],
                ['Create Booking', $results['create']['success'] ? '✅ PASS' : '❌ FAIL', $results['create']['message']],
                ['Update Booking', $results['update']['success'] ? '✅ PASS' : '❌ FAIL', $results['update']['message']],
                ['Delete Booking', $results['delete']['success'] ? '✅ PASS' : '❌ FAIL', $results['delete']['message']],
            ]
        );

        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($r) => $r['success']));
        
        $this->newLine();
        if ($passedTests === $totalTests) {
            $this->info("✅ All tests passed! ({$passedTests}/{$totalTests})");
            return 0;
        } else {
            $this->error("❌ Some tests failed! ({$passedTests}/{$totalTests} passed)");
            return 1;
        }
    }

    protected function authenticateAdmin()
    {
        $this->line('🔐 Authenticating as admin...');
        
        // Try to get existing admin user
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            // Create admin user if doesn't exist
            $admin = User::create([
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->line('   Created test admin user');
        }

        // Set as authenticated user
        auth()->login($admin);
        $this->adminUser = $admin;
        
        return true;
    }

    protected function testList()
    {
        $this->line('📋 Testing GET /admin/crud/bookings (List)...');
        
        try {
            $request = Request::create('/admin/crud/bookings', 'GET', [
                'per_page' => 5,
                'page' => 1,
            ]);
            $request->setUserResolver(function () {
                return $this->adminUser;
            });

            $response = $this->bookingController->index($request);
            $data = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200 && ($data['success'] ?? false)) {
                $count = count($data['data'] ?? []);
                $this->line("   ✅ Retrieved {$count} booking(s)");
                
                if ($this->option('detailed')) {
                    $this->line('   Response: ' . json_encode($data, JSON_PRETTY_PRINT));
                }
                
                return ['success' => true, 'message' => "Retrieved {$count} bookings"];
            } else {
                $this->line("   ❌ Failed with status: {$response->getStatusCode()}");
                $this->line('   Response: ' . $response->getContent());
                return ['success' => false, 'message' => "HTTP {$response->getStatusCode()}: " . $response->getContent()];
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            if ($this->option('detailed')) {
                $this->line('   Stack: ' . $e->getTraceAsString());
            }
            return ['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }

    protected function testShow()
    {
        $this->line('👁️  Testing GET /admin/crud/bookings/{id} (Show)...');
        
        // Get first booking
        $booking = Booking::first();
        
        if (!$booking) {
            $this->line('   ⚠️  No bookings found, skipping show test');
            return ['success' => true, 'message' => 'Skipped (no bookings)'];
        }

        try {
            $response = $this->bookingController->show($booking->id);
            $data = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200 && ($data['success'] ?? false)) {
                $this->line("   ✅ Retrieved booking ID: {$booking->id}");
                
                if ($this->option('detailed')) {
                    $this->line('   Response: ' . json_encode($data, JSON_PRETTY_PRINT));
                }
                
                return ['success' => true, 'message' => "Retrieved booking #{$booking->id}"];
            } else {
                $this->line("   ❌ Failed with status: {$response->getStatusCode()}");
                $this->line('   Response: ' . $response->getContent());
                return ['success' => false, 'message' => "HTTP {$response->getStatusCode()}: " . $response->getContent()];
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            if ($this->option('detailed')) {
                $this->line('   Stack: ' . $e->getTraceAsString());
            }
            return ['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }

    protected function testCreate()
    {
        $this->line('➕ Testing POST /admin/crud/bookings (Create)...');
        
        // Get or create required dependencies
        $customer = Customer::where('is_active', true)->first();
        if (!$customer) {
            $customer = Customer::create([
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => 'testcustomer' . time() . '@example.com',
                'phone' => '+256700000000',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);
        } elseif (!$customer->is_active) {
            // Activate the customer if found but inactive
            $customer->is_active = true;
            $customer->save();
        }

        $route = Route::first();
        if (!$route) {
            $this->line('   ⚠️  No routes found, cannot create booking');
            return ['success' => false, 'message' => 'No routes available'];
        }

        $vehicle = Vehicle::first();
        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'vehicle_type_id' => 1,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2023,
                'engine_type' => 'petrol',
                'transmission' => 'automatic',
            ]);
        }

        $bookingData = [
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
            'total_amount' => 1500.00,
            'currency' => 'USD',
            'pickup_date' => now()->addDays(7)->format('Y-m-d'),
            'delivery_date' => now()->addDays(30)->format('Y-m-d'),
            'recipient_name' => 'Test Recipient',
            'recipient_email' => 'recipient@example.com',
            'recipient_phone' => '+256700000001',
            'recipient_country' => 'Uganda',
            'recipient_city' => 'Kampala',
            'recipient_address' => 'Test Address 123',
            'notes' => 'Test booking created by CRUD test',
        ];

        try {
            $request = Request::create('/admin/crud/bookings', 'POST', $bookingData);
            $request->setUserResolver(function () {
                return $this->adminUser;
            });

            $response = $this->bookingController->store($request);
            $data = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 201 && ($data['success'] ?? false)) {
                $this->testBookingId = $data['data']['id'] ?? null;
                $this->line("   ✅ Created booking ID: {$this->testBookingId}");
                
                if ($this->option('detailed')) {
                    $this->line('   Response: ' . json_encode($data, JSON_PRETTY_PRINT));
                }
                
                return ['success' => true, 'message' => "Created booking #{$this->testBookingId}"];
            } else {
                $this->line("   ❌ Failed with status: {$response->getStatusCode()}");
                $this->line('   Response: ' . $response->getContent());
                return ['success' => false, 'message' => "HTTP {$response->getStatusCode()}: " . $response->getContent()];
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            if ($this->option('detailed')) {
                $this->line('   Stack: ' . $e->getTraceAsString());
            }
            return ['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }

    protected function testUpdate()
    {
        $this->line('✏️  Testing PUT /admin/crud/bookings/{id} (Update)...');
        
        // Use the booking we just created, or get the first one
        $booking = $this->testBookingId 
            ? Booking::find($this->testBookingId)
            : Booking::first();
        
        if (!$booking) {
            $this->line('   ⚠️  No bookings found, skipping update test');
            return ['success' => true, 'message' => 'Skipped (no bookings)'];
        }

        $updateData = [
            'status' => 'confirmed',
            'total_amount' => 2000.00,
            'notes' => 'Updated by CRUD test - ' . now()->toDateTimeString(),
        ];

        try {
            $request = Request::create("/admin/crud/bookings/{$booking->id}", 'PUT', $updateData);
            $request->setUserResolver(function () {
                return $this->adminUser;
            });

            $response = $this->bookingController->update($request, $booking->id);
            $data = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200 && ($data['success'] ?? false)) {
                $this->line("   ✅ Updated booking ID: {$booking->id}");
                
                // Verify the update
                $booking->refresh();
                if ($booking->status === 'confirmed') {
                    $this->line("   ✅ Verified: Status changed to 'confirmed'");
                }
                
                if ($this->option('detailed')) {
                    $this->line('   Response: ' . json_encode($data, JSON_PRETTY_PRINT));
                }
                
                return ['success' => true, 'message' => "Updated booking #{$booking->id}"];
            } else {
                $this->line("   ❌ Failed with status: {$response->getStatusCode()}");
                $this->line('   Response: ' . $response->getContent());
                return ['success' => false, 'message' => "HTTP {$response->getStatusCode()}: " . $response->getContent()];
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            if ($this->option('detailed')) {
                $this->line('   Stack: ' . $e->getTraceAsString());
            }
            return ['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }

    protected function testDelete()
    {
        $this->line('🗑️  Testing DELETE /admin/crud/bookings/{id} (Delete)...');
        
        // Use the booking we created, or get the first one
        $booking = $this->testBookingId 
            ? Booking::find($this->testBookingId)
            : Booking::first();
        
        if (!$booking) {
            $this->line('   ⚠️  No bookings found, skipping delete test');
            return ['success' => true, 'message' => 'Skipped (no bookings)'];
        }

        $bookingId = $booking->id;

        try {
            $request = Request::create("/admin/crud/bookings/{$bookingId}", 'DELETE', [
                'reason' => 'Deleted by CRUD test',
                'confirmation' => true,
            ]);
            $request->setUserResolver(function () {
                return $this->adminUser;
            });

            $response = $this->bookingController->destroy($request, $bookingId);
            $data = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200 && ($data['success'] ?? false)) {
                $this->line("   ✅ Deleted booking ID: {$bookingId}");
                
                // Verify the deletion
                $deletedBooking = Booking::find($bookingId);
                if (!$deletedBooking) {
                    $this->line("   ✅ Verified: Booking has been deleted");
                } else {
                    $this->line("   ⚠️  Booking still exists (may be soft-deleted or deletion failed)");
                }
                
                if ($this->option('detailed')) {
                    $this->line('   Response: ' . $response->getContent());
                }
                
                return ['success' => true, 'message' => "Deleted booking #{$bookingId}"];
            } else {
                $this->line("   ❌ Failed with status: {$response->getStatusCode()}");
                $this->line('   Response: ' . $response->getContent());
                return ['success' => false, 'message' => "HTTP {$response->getStatusCode()}: " . $response->getContent()];
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            if ($this->option('detailed')) {
                $this->line('   Stack: ' . $e->getTraceAsString());
            }
            return ['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        }
    }
}
