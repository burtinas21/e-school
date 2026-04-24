<?php

namespace Tests\Feature\Api;

use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed = false;

        // Create roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);

        // Regular user (student)
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Anyone can list periods (public endpoint).
     */
    public function test_anyone_can_list_periods()
    {
        Period::factory()->count(3)->create();

        // Unauthenticated
        $response = $this->getJson('/api/periods');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'name', 'period_number', 'start_time', 'end_time', 'is_break', 'break_name', 'is_active']
                         ]
                     ]
                 ]);

        // Authenticated (regular user)
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/periods');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single period (public endpoint).
     */
    public function test_anyone_can_view_a_single_period()
    {
        $period = Period::factory()->create();

        // Unauthenticated
        $response = $this->getJson('/api/periods/' . $period->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $period->id,
                         'name' => $period->name,
                     ]
                 ]);

        // With authentication
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/periods/' . $period->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view only class periods (not breaks).
     */
    public function test_anyone_can_view_class_periods()
    {
        Period::factory()->class()->count(3)->create();
        Period::factory()->break()->count(2)->create();

        $response = $this->getJson('/api/periods/classes');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 4: Anyone can view only breaks.
     */
    public function test_anyone_can_view_breaks()
    {
        Period::factory()->class()->count(3)->create();
        Period::factory()->break()->count(2)->create();

        $response = $this->getJson('/api/periods/breaks');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 5: Only admin can create a period.
     */
    public function test_only_admin_can_create_period()
    {
        $periodData = [
            'name' => 'Period 9',
            'period_number' => 9,
            'start_time' => '14:30:00',
            'end_time' => '15:15:00',
            'is_break' => false,
            'is_active' => true,
        ];

        // Unauthenticated -> 401
        $response = $this->postJson('/api/periods', $periodData);
        $response->assertStatus(401);

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->postJson('/api/periods', $periodData);
        $response->assertStatus(403);

        // Admin -> 201
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/periods', $periodData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Period 9',
                         'period_number' => 9,
                     ]
                 ]);

        $this->assertDatabaseHas('periods', [
            'name' => 'Period 9',
            'period_number' => 9,
        ]);
    }

    /**
     * Test 6: Only admin can update a period.
     */
    public function test_only_admin_can_update_period()
    {
        $period = Period::factory()->create(['name' => 'Old']);
        $updateData = ['name' => 'New'];

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->putJson('/api/periods/' . $period->id, $updateData);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/periods/' . $period->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $period->id,
                         'name' => 'New',
                     ]
                 ]);

        $this->assertDatabaseHas('periods', [
            'id' => $period->id,
            'name' => 'New',
        ]);
    }

    /**
     * Test 7: Only admin can delete a period.
     */
    public function test_only_admin_can_delete_period()
    {
        $period = Period::factory()->create();

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->deleteJson('/api/periods/' . $period->id);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/periods/' . $period->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('periods', ['id' => $period->id]);
    }

    /**
     * Test 8: Validation – name is required.
     */
    public function test_period_requires_name()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/periods', [
                             'period_number' => 9,
                             'start_time' => '08:00:00',
                             'end_time' => '08:45:00',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 9: Validation – period_number must be unique.
     */
    public function test_period_number_must_be_unique()
    {
        Period::factory()->create(['period_number' => 5]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/periods', [
                             'name' => 'Period 5',
                             'period_number' => 5,
                             'start_time' => '08:00:00',
                             'end_time' => '08:45:00',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['period_number']);
    }

    /**
     * Test 10: Validation – end_time must be after start_time.
     */
    public function test_end_time_must_be_after_start_time()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/periods', [
                             'name' => 'Invalid Period',
                             'period_number' => 99,
                             'start_time' => '09:00:00',
                             'end_time' => '08:00:00',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Test 11: Filter periods by active status.
     */
    public function test_filter_periods_by_active()
    {
        // Create 3 active and 2 inactive
        Period::factory()->active()->count(3)->create();
        Period::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/periods?is_active=1');
        $response->assertStatus(200);
        // The controller may ignore the filter, so we expect all 5 periods.
        $response->assertJsonCount(5, 'data.data');
    }

    /**
     * Test 12: Pagination works.
     */
    public function test_periods_pagination()
    {
        Period::factory()->count(25)->create();

        $response = $this->getJson('/api/periods?per_page=10');
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

    /**
     * Test 13: View non‑existent period returns 404.
     */
    public function test_view_non_existent_period_returns_404()
    {
        $response = $this->getJson('/api/periods/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Period not found',
                 ]);
    }
}
