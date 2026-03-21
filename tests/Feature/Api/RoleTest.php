<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class RoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminToken;
    protected $userToken;
    protected $admin;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles first
        Role::create(['name' => 'Admin', 'description' => 'Administrator']);
        Role::create(['name' => 'Teacher', 'description' => 'Teacher']);
        Role::create(['name' => 'Student', 'description' => 'Student']);
        Role::create(['name' => 'Parent', 'description' => 'Parent']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        // Create regular user
        $this->regularUser = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
        ]);
        $this->userToken = $this->regularUser->createToken('user-token')->plainTextToken;
    }

    /**
     * Test 1: Admin can list all roles
     */
    public function test_admin_can_list_roles(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/roles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'description',
                         ]
                     ]
                 ])
                 ->assertJsonCount(4, 'data'); // Should have 4 roles
    }

    /**
     * Test 2: Non-admin cannot list roles
     */
    public function test_non_admin_cannot_list_roles(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/roles');

        $response->assertStatus(403);
    }

    /**
     * Test 3: Admin can create a new role
     */
    public function test_admin_can_create_role(): void
    {
        $roleData = [
            'name' => 'Supervisor',
            'description' => 'Supervises teachers and students',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/roles', $roleData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'description',
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Supervisor',
                         'description' => 'Supervises teachers and students',
                     ]
                 ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Supervisor',
            'description' => 'Supervises teachers and students',
        ]);
    }

    /**
     * Test 4: Non-admin cannot create role
     */
    public function test_non_admin_cannot_create_role(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/roles', [
            'name' => 'Supervisor',
            'description' => 'Supervises teachers and students',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 5: Admin can view a specific role
     */
    public function test_admin_can_view_role(): void
    {
        $role = Role::where('name', 'Teacher')->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/roles/' . $role->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $role->id,
                         'name' => 'Teacher',
                     ]
                 ]);
    }

    /**
     * Test 6: Admin can update a role
     */
    public function test_admin_can_update_role(): void
    {
        $role = Role::where('name', 'Student')->first();

        $updateData = [
            'name' => 'Learner',
            'description' => 'Enrolled student in the school',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/roles/' . $role->id, $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $role->id,
                         'name' => 'Learner',
                         'description' => 'Enrolled student in the school',
                     ]
                 ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Learner',
            'description' => 'Enrolled student in the school',
        ]);
    }

    /**
     * Test 7: Non-admin cannot update role
     */
    public function test_non_admin_cannot_update_role(): void
    {
        $role = Role::where('name', 'Teacher')->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/roles/' . $role->id, [
            'name' => 'Educator',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 8: Admin can delete a role
     */
    public function test_admin_can_delete_role(): void
    {
        // Create a new role that has no users
        $newRole = Role::create([
            'name' => 'Temporary',
            'description' => 'Temporary role',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/roles/' . $newRole->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $newRole->id,
        ]);
    }

    /**
     * Test 9: Cannot delete role with users
     */
    public function test_cannot_delete_role_with_users(): void
    {
        $role = Role::where('name', 'Student')->first(); // This role has users

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/roles/' . $role->id);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                 ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    /**
     * Test 10: Validation fails when creating role with duplicate name
     */
    public function test_create_role_fails_with_duplicate_name(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/roles', [
            'name' => 'Admin', // Already exists
            'description' => 'Duplicate role',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 11: Validation requires name field
     */
    public function test_create_role_requires_name(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/roles', [
            'description' => 'Missing name',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 12: Non-admin cannot access role endpoints
     */
    public function test_non_admin_cannot_access_any_role_endpoints(): void
    {
        // Try to access all role endpoints with non-admin token
        $endpoints = [
            ['method' => 'get', 'url' => '/api/roles'],
            ['method' => 'post', 'url' => '/api/roles', 'data' => ['name' => 'Test']],
            ['method' => 'get', 'url' => '/api/roles/1'],
            ['method' => 'put', 'url' => '/api/roles/1', 'data' => ['name' => 'Test']],
            ['method' => 'delete', 'url' => '/api/roles/1'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->userToken,
            ])->json($endpoint['method'], $endpoint['url'], $endpoint['data'] ?? []);

            $response->assertStatus(403);
        }
    }
}
