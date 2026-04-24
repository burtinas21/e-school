<?php

namespace Tests\Feature\Api;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTest extends TestCase
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
     * Test 1: Anyone can list all teachers (public endpoint).
     */
    public function test_anyone_can_list_teachers(): void
    {
        Teacher::factory()->count(3)->create();

        // Unauthenticated
        $response = $this->getJson('/api/teachers');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'user_id', 'employee_id', 'qualification', 'hire_date', 'is_active']
                     ]
                 ]);

        // Authenticated (regular user)
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teachers');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single teacher (public endpoint).
     */
    public function test_anyone_can_view_a_single_teacher(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->getJson('/api/teachers/' . $teacher->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $teacher->id,
                         'employee_id' => $teacher->employee_id,
                     ]
                 ]);

        // With authentication
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teachers/' . $teacher->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Only admin can create a teacher.
     */
    public function test_only_admin_can_create_a_teacher(): void
    {
        $teacherData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '1234567890',
            'employee_id' => 'TCH999',
            'qualification' => 'M.Ed',
            'hire_date' => '2020-01-01',
        ];

        // Unauthenticated -> 401
        $response = $this->postJson('/api/teachers', $teacherData);
        $response->assertStatus(401);

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->postJson('/api/teachers', $teacherData);
        $response->assertStatus(403);

        // Admin -> 201
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/teachers', $teacherData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'employee_id' => 'TCH999',
                         'qualification' => 'M.Ed',
                     ]
                 ]);

        $this->assertDatabaseHas('teachers', [
            'employee_id' => 'TCH999',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role_id' => 2,
        ]);
    }

    /**
     * Test 4: Only admin can update a teacher.
     */
    public function test_only_admin_can_update_a_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $updateData = ['qualification' => 'Ph.D'];

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->putJson('/api/teachers/' . $teacher->id, $updateData);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/teachers/' . $teacher->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $teacher->id,
                         'qualification' => 'Ph.D',
                     ]
                 ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'qualification' => 'Ph.D',
        ]);
    }

    /**
     * Test 5: Only admin can delete a teacher.
     */
    public function test_only_admin_can_delete_a_teacher(): void
    {
        $teacher = Teacher::factory()->create();

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->deleteJson('/api/teachers/' . $teacher->id);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/teachers/' . $teacher->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $teacher->user_id]);
    }

    /**
     * Test 6: Validation – email must be unique.
     */
    public function test_email_must_be_unique(): void
    {
        $existingTeacher = Teacher::factory()->create();
        $existingUser = $existingTeacher->user;

        $teacherData = [
            'name' => 'Another Teacher',
            'email' => $existingUser->email, // duplicate
            'password' => 'password123',
            'employee_id' => 'TCH888',
            'qualification' => 'B.Ed',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/teachers', $teacherData);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 7: Validation – employee_id must be unique.
     */
    public function test_employee_id_must_be_unique(): void
    {
        $existingTeacher = Teacher::factory()->create();

        $teacherData = [
            'name' => 'Another Teacher',
            'email' => 'unique@example.com',
            'password' => 'password123',
            'employee_id' => $existingTeacher->employee_id, // duplicate
            'qualification' => 'B.Ed',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/teachers', $teacherData);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['employee_id']);
    }

    /**
     * Test 8: View non‑existent teacher returns 404.
     */
    public function test_view_non_existent_teacher_returns_404(): void
    {
        $response = $this->getJson('/api/teachers/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Teacher not found',
                 ]);
    }

    /**
 * Test 9: Inactive teachers are still returned in the index.
 */
public function test_inactive_teachers_appear_in_list(): void
{
    // Create an active teacher (default)
    $activeTeacher = Teacher::factory()->create();
    // Create an inactive teacher
    $inactiveTeacher = Teacher::factory()->inactive()->create();

    $response = $this->getJson('/api/teachers');
    $response->assertStatus(200)
             ->assertJsonCount(2, 'data');  // assuming only these two exist

    $teacherIds = collect($response->json('data'))->pluck('id');
    $this->assertContains($activeTeacher->id, $teacherIds);
    $this->assertContains($inactiveTeacher->id, $teacherIds);
}

/**
 * Test 10 : Factory state 'inactive' correctly sets is_active to false.
 */
public function test_factory_inactive_state_sets_is_active_false(): void
{
    $teacher = Teacher::factory()->inactive()->create();
    $this->assertFalse($teacher->is_active);
}
}
