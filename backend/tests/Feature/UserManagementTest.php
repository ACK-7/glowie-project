<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserManagementTest extends TestCase
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
    public function admin_can_view_users_list()
    {
        Sanctum::actingAs($this->adminUser);

        // Create some test users
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/crud/users');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'role',
                                'role_label',
                                'is_active',
                                'created_at',
                            ]
                        ],
                        'meta' => [
                            'pagination' => [
                                'current_page',
                                'per_page',
                                'total',
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_OPERATOR,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/admin/crud/users', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_active',
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
        ]);
    }

    /** @test */
    public function admin_can_view_specific_user()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $response = $this->getJson("/api/admin/crud/users/{$user->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'permissions',
                        'activity_summary',
                        'recent_activity',
                    ]
                ]);
    }

    /** @test */
    public function admin_can_update_user()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'role' => User::ROLE_MANAGER,
        ];

        $response = $this->putJson("/api/admin/crud/users/{$user->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'role',
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    /** @test */
    public function admin_can_activate_user()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'is_active' => false,
        ]);

        $response = $this->patchJson("/api/admin/crud/users/{$user->id}/activate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_deactivate_user()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/admin/crud/users/{$user->id}/deactivate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function admin_can_get_roles_and_permissions()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/crud/users/roles-permissions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'roles',
                        'permissions',
                        'role_permissions',
                        'available_permissions',
                    ]
                ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_user_management()
    {
        $response = $this->getJson('/api/admin/crud/users');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_without_permission_cannot_access_user_management()
    {
        $operatorUser = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'permissions' => [], // No user management permissions
        ]);

        Sanctum::actingAs($operatorUser);

        $response = $this->getJson('/api/admin/crud/users');

        $response->assertStatus(403);
    }
}