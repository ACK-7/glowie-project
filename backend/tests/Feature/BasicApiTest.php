<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicApiTest extends TestCase
{
    /**
     * Test basic API health check endpoint
     */
    public function test_api_health_check_returns_success()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/health');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'ok'
                ]);
    }

    /**
     * Test basic API test endpoint
     */
    public function test_api_test_endpoint_returns_success()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/test');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'API is working!'
                ]);
    }

    /**
     * Test API returns proper JSON structure
     */
    public function test_api_returns_proper_json_structure()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/test/success');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /**
     * Test API error handling
     */
    public function test_api_error_handling_returns_proper_format()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/test/error');

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /**
     * Test API validation endpoint
     */
    public function test_api_validation_returns_proper_error_format()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('/api/test/validation', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }
}