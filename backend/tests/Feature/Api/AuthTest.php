<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test 1: User can register via API
     * Tests: name, email, password, phone, role_id, is_active
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
           // 'phone' => '0911223344',
        ];

        $response = $this->postJson('/api/register', $userData);

        // Check response status and structure
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role_id',
                     ],
                     'token'
                 ]);

        // Check response values
        $response->assertJson([
            'success' => true,
            'user' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role_id' => 3,  // Default student role
            ]
        ]);

        // Check database
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role_id' => 3,
           // 'phone' => '0911223344',
            'is_active' => 1,  // Should be active
        ]);
    }

    /**
     * Test 2: Registration fails with missing fields
     */
    public function test_registration_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User'
            //  Missing email, password
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test 3: Registration fails with mismatched passwords
     */
    public function test_registration_fails_with_password_mismatch(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test 4: User can login
     */
    public function test_user_can_login(): void
    {
        // First create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => 3,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'user',
                     'token',
                     'role_id'
                 ])
                 ->assertJson([
                     'success' => true,
                     'user' => [
                         'email' => 'test@example.com',
                     ],
                     'role_id' => 3,
                 ]);
    }

    /**
     * Test 5: Login fails with wrong password
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 6: User can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out'
                 ]);

        // Check token was deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Test 7: User can get profile
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile Test',
            'email' => 'profile@test.com',
           // 'phone' => '0911000111',
            'role_id' => 2,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Profile Test',
                         'email' => 'profile@test.com',
                         //'phone' => '0911000111',
                         'role_id' => 2,
                     ]
                 ]);
    }

    /**
     * Test 8: Protected routes require authentication
     */
    public function test_protected_routes_require_auth(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    /**
     * Test 9: User cannot register with existing email
     */
    public function test_cannot_register_with_existing_email(): void
    {
        // Create first user
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        // Try to register with same email
        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 10: Registration without phone works
     */
    public function test_registration_without_phone_succeeds(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'No Phone User',
            'email' => 'nophone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // No phone field
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'nophone@example.com',
            //'phone' => null,  // Phone should be null
        ]);
    }
}
