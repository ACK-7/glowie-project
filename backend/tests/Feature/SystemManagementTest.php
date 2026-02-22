<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class SystemManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a super admin user for testing
        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_view_system_settings()
    {
        Sanctum::actingAs($this->adminUser);

        // Create some test settings
        SystemSetting::create([
            'key_name' => 'test.setting1',
            'value' => 'test_value',
            'data_type' => 'string',
            'description' => 'Test setting 1',
        ]);

        SystemSetting::create([
            'key_name' => 'test.setting2',
            'value' => '123',
            'data_type' => 'integer',
            'description' => 'Test setting 2',
        ]);

        $response = $this->getJson('/api/admin/system/settings');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'key_name',
                                'value',
                                'data_type',
                                'description',
                                'is_public',
                            ]
                        ],
                        'meta' => [
                            'pagination'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function admin_can_get_specific_setting()
    {
        Sanctum::actingAs($this->adminUser);

        $setting = SystemSetting::create([
            'key_name' => 'test.specific_setting',
            'value' => 'specific_value',
            'data_type' => 'string',
            'description' => 'Specific test setting',
        ]);

        $response = $this->getJson("/api/admin/system/settings/{$setting->key_name}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'key_name',
                        'value',
                        'data_type',
                        'description',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'key_name' => 'test.specific_setting',
                        'value' => 'specific_value',
                    ]
                ]);
    }

    /** @test */
    public function admin_can_update_system_settings()
    {
        Sanctum::actingAs($this->adminUser);

        $settingsData = [
            'settings' => [
                [
                    'key' => 'test.new_setting',
                    'value' => 'new_value',
                    'description' => 'New test setting',
                    'is_public' => false,
                ],
                [
                    'key' => 'test.another_setting',
                    'value' => 42,
                    'description' => 'Another test setting',
                    'is_public' => true,
                ]
            ]
        ];

        $response = $this->putJson('/api/admin/system/settings', $settingsData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'updated_settings',
                        'changes_count',
                    ]
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key_name' => 'test.new_setting',
            'value' => 'new_value',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key_name' => 'test.another_setting',
            'value' => '42',
        ]);
    }

    /** @test */
    public function admin_can_get_system_health()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/system/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'status',
                        'timestamp',
                        'checks' => [
                            'database' => [
                                'status',
                                'message',
                            ],
                            'cache' => [
                                'status',
                                'message',
                            ],
                            'storage' => [
                                'status',
                                'message',
                            ],
                        ]
                    ]
                ]);
    }

    /** @test */
    public function admin_can_get_system_metrics()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/system/metrics?period=24h');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'period',
                        'start_time',
                        'end_time',
                        'system',
                        'database',
                        'api',
                        'users',
                        'business',
                    ]
                ]);
    }

    /** @test */
    public function admin_can_initialize_default_settings()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/system/settings/initialize');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'initialized_at',
                        'settings_count',
                    ]
                ]);

        // Check that some default settings were created
        $this->assertDatabaseHas('system_settings', [
            'key_name' => 'app.name',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key_name' => 'business.default_currency',
        ]);
    }

    /** @test */
    public function admin_can_clear_cache()
    {
        Sanctum::actingAs($this->adminUser);

        $cacheData = [
            'cache_types' => ['application', 'config']
        ];

        $response = $this->postJson('/api/admin/system/cache/clear', $cacheData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'cleared_caches',
                        'timestamp',
                    ]
                ]);
    }

    /** @test */
    public function admin_can_view_configuration_history()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/system/configuration/history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data',
                        'meta' => [
                            'pagination'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_system_management()
    {
        $response = $this->getJson('/api/admin/system/settings');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_without_permission_cannot_access_system_settings()
    {
        $operatorUser = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'permissions' => [], // No settings permissions
        ]);

        Sanctum::actingAs($operatorUser);

        $response = $this->getJson('/api/admin/system/settings');

        $response->assertStatus(403);
    }
}