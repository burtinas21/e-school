<?php

namespace Tests\Feature\Api;

use App\Models\Subject;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
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
     * Test 1: Anyone can list all subjects (public endpoint).
     */
    public function test_anyone_can_list_subjects(): void
    {
        Subject::factory()->count(3)->create();

        // Unauthenticated
        $response = $this->getJson('/api/subjects');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'name', 'grade_id', 'subject_code', 'is_active']
                     ]
                 ]);

        // Authenticated (regular user)
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/subjects');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single subject (public endpoint).
     */
    public function test_anyone_can_view_a_single_subject(): void
    {
        $subject = Subject::factory()->create();

        $response = $this->getJson('/api/subjects/' . $subject->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $subject->id,
                         'name' => $subject->name,
                     ]
                 ]);

        // With authentication
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/subjects/' . $subject->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view subjects by grade (public endpoint).
     */
    public function test_anyone_can_view_subjects_by_grade(): void
    {
        $grade = Grade::factory()->create();
        Subject::factory()->count(2)->create(['grade_id' => $grade->id]);
        Subject::factory()->create(); // subject in another grade

        $response = $this->getJson('/api/subjects/by-grade/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 4: Only admin can create a subject (protected endpoint).
     */
    public function test_only_admin_can_create_a_subject(): void
    {
        $grade = Grade::factory()->create();
        $subjectData = [
            'name' => 'Mathematics',
            'grade_id' => $grade->id,
            'subject_code' => 'MATH101',
            'credits' => 4.0,
            'is_core' => true,
            'is_active' => true,
        ];

        // Unauthenticated -> 401
        $response = $this->postJson('/api/subjects', $subjectData);
        $response->assertStatus(401);

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->postJson('/api/subjects', $subjectData);
        $response->assertStatus(403);

        // Admin -> 201
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/subjects', $subjectData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Mathematics',
                         'grade_id' => $grade->id,
                         'subject_code' => 'MATH101',
                     ]
                 ]);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'grade_id' => $grade->id,
            'subject_code' => 'MATH101',
        ]);
    }

    /**
     * Test 5: Only admin can update a subject.
     */
    public function test_only_admin_can_update_a_subject(): void
    {
        $subject = Subject::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'New Name'];

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->putJson('/api/subjects/' . $subject->id, $updateData);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/subjects/' . $subject->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $subject->id,
                         'name' => 'New Name',
                     ]
                 ]);

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test 6: Only admin can delete a subject.
     */
    public function test_only_admin_can_delete_a_subject(): void
    {
        $subject = Subject::factory()->create();

        // Regular user -> 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->deleteJson('/api/subjects/' . $subject->id);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/subjects/' . $subject->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    /**
     * Test 7: Validation – name is required when creating a subject.
     */
    public function test_subject_requires_name(): void
    {
        $grade = Grade::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/subjects', ['grade_id' => $grade->id]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 8: Validation – subject_code must be unique.
     */
    public function test_subject_code_must_be_unique(): void
    {
        $grade = Grade::factory()->create();
        Subject::factory()->create(['subject_code' => 'CODE123', 'grade_id' => $grade->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/subjects', [
                             'name' => 'Another Subject',
                             'grade_id' => $grade->id,
                             'subject_code' => 'CODE123',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['subject_code']);
    }

    /**
     * Test 9: Filter subjects by grade using query parameter.
     */
    public function test_filter_subjects_by_grade(): void
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();

        Subject::factory()->create(['grade_id' => $grade1->id, 'name' => 'Math']);
        Subject::factory()->create(['grade_id' => $grade1->id, 'name' => 'Science']);
        Subject::factory()->create(['grade_id' => $grade2->id, 'name' => 'History']);

        $response = $this->getJson('/api/subjects/by-grade/' . $grade1->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 10: View non‑existent subject returns 404.
     */
    public function test_view_non_existent_subject_returns_404(): void
    {
        $response = $this->getJson('/api/subjects/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Subject not found',
                 ]);
    }
}
