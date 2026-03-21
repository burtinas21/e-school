<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminToken;
    protected $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles first
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Teacher']);
        Role::create(['name' => 'Student']);
        Role::create(['name' => 'Guardian']);

        // Create admin user
        $admin = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]);
        $this->adminToken = $admin->createToken('admin-token')->plainTextToken;

        // Create regular user
        $user = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
        ]);
        $this->userToken = $user->createToken('user-token')->plainTextToken;
    }

    /**
     * Test 1: Admin can list all users
     */
    public function test_admin_can_list_users(): void
    {
        // Create additional users
        User::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'email',
                                 'role_id',
                                 'is_active',
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * Test 2: Non-admin cannot list users
     */
    public function test_non_admin_cannot_list_users(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/users');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test 3: Admin can create new user
     */
    public function test_admin_can_create_user(): void
    {
        $userData = [
            'name' => 'New Created User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '0911223344',
            'role_id' => 3,
            'is_active' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/users', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'role_id',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New Created User',
            'role_id' => 3,
        ]);
    }

    /**
     * Test 4: Admin can view single user
     */
    public function test_admin_can_view_user(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users/' . $user->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $user->id,
                         'name' => $user->name,
                         'email' => $user->email,
                     ]
                 ]);
    }

    /**
     * Test 5: User can view their own profile
     */
    public function test_user_can_view_own_profile(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'role_id',
                     ]
                 ]);
    }

    /**
     * Test 6: Admin can update user
     */
    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '0999999999',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/users/' . $user->id, $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '0999999999',
        ]);
    }

    /**
     * Test 7: User cannot update other users
     */
    public function test_user_cannot_update_other_users(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/users/' . $otherUser->id, [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 8: Admin can delete user
     */
    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/users/' . $user->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test 9: User cannot delete other users
     */
    public function test_user_cannot_delete_other_users(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/users/' . $otherUser->id);

        $response->assertStatus(403);
    }

    /**
     * Test 10: User cannot delete themselves
     */
    public function test_user_cannot_delete_themselves(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/users/1'); // Admin trying to delete themselves

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    /**
     * Test 11: Filter users by role
     */
    public function test_filter_users_by_role(): void
    {
        // Create teachers
        User::factory()->count(3)->create(['role_id' => 2]);
        // Create students
        User::factory()->count(5)->create(['role_id' => 3]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users?role_id=3');

        $response->assertStatus(200);

        // Count should be at least 5 students + 1 from setup = 6
        $this->assertTrue(count($response['data']['data']) >= 5);
    }

    /**
     * Test 12: Search users by name or email
     */
    public function test_search_users(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users?search=John');

        $response->assertStatus(200);

        $users = collect($response['data']['data']);
        $this->assertTrue($users->contains('name', 'John Doe'));
    }

    /**
     * Test 13: Filter active/inactive users
     */
    public function test_filter_active_users(): void
    {
        User::factory()->count(3)->create(['is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users?is_active=1');

        $response->assertStatus(200);

        $users = collect($response['data']['data']);
        $this->assertTrue($users->every(fn($user) => $user['is_active'] == 1));
    }

    /**
     * Test 14: Validation fails with duplicate email
     */
    public function test_create_user_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/users', [
            'name' => 'New User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 3,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 15: Pagination works
     */
    public function test_users_pagination(): void
    {
        User::factory()->count(20)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/users?per_page=10');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'current_page',
                         'data',
                         'per_page',
                         'total',
                     ]
                 ]);

        $this->assertEquals(10, $response['data']['per_page']);
    }
}
