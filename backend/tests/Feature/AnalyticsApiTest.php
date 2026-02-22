<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a test user
        $user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        Sanctum::actingAs($user);
    }

    /**
     * Test analytics dashboard endpoint
     */
    public function test_analytics_dashboard_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test revenue analytics endpoint
     */
    public function test_revenue_analytics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/revenue');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test booking analytics endpoint
     */
    public function test_booking_analytics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/bookings');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test customer analytics endpoint
     */
    public function test_customer_analytics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/customers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test operational analytics endpoint
     */
    public function test_operational_analytics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/operational');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test trend analysis endpoint
     */
    public function test_trend_analysis_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/trends?metric=revenue');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test comparative analysis endpoint
     */
    public function test_comparative_analysis_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/comparative?' . http_build_query([
            'current_start' => '2024-01-01',
            'current_end' => '2024-01-31',
            'previous_start' => '2023-12-01',
            'previous_end' => '2023-12-31'
        ]));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test predictive analytics endpoint
     */
    public function test_predictive_analytics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/analytics/predictive?metric=revenue');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    /**
     * Test export functionality
     */
    public function test_export_report_endpoint(): void
    {
        $response = $this->postJson('/api/admin/analytics/export', [
            'report_type' => 'revenue',
            'format' => 'csv',
            'period' => '30_days'
        ]);

        // Should return a file download response
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 302,
            'Export endpoint should return success or redirect'
        );
    }

    /**
     * Test validation on analytics endpoints
     */
    public function test_analytics_validation(): void
    {
        // Test invalid period
        $response = $this->getJson('/api/admin/analytics/revenue?period=invalid');
        $response->assertStatus(422);

        // Test invalid metric for trends
        $response = $this->getJson('/api/admin/analytics/trends?metric=invalid');
        $response->assertStatus(422);

        // Test missing required parameters for comparative analysis
        $response = $this->getJson('/api/admin/analytics/comparative');
        $response->assertStatus(422);
    }
}