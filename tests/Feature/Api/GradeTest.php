<?php

namespace Tests\Feature\Api;

use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    /**
     * Set up the test environment before each test.
     * Creates roles, admin user, and a regular student user.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Do not run any database seeders – we will create data manually.
        $this->seed = false;

        // Create the basic roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Create an admin user (role_id = 1) with a unique email.
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);

        // Create a regular student user (role_id = 3) with a unique email.
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Anyone can list all grades (public endpoint).
     * Verifies that both unauthenticated and authenticated requests succeed.
     */
    public function test_anyone_can_list_grades(): void
    {
        // Create 3 grades using the factory
        Grade::factory()->count(3)->create();

        // Without authentication – should succeed
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'name', 'level', 'is_active']
                         ]
                     ]
                 ]);

        // With authentication (regular user) – should also succeed
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/grades');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single grade (public endpoint).
     * Checks that a grade can be retrieved without authentication.
     */
    public function test_anyone_can_view_a_single_grade(): void
    {
        $grade = Grade::factory()->create();

        // Unauthenticated request
        $response = $this->getJson('/api/grades/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $grade->id,
                         'name' => $grade->name,
                     ]
                 ]);

        // Authenticated request (regular user) – should also work
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/grades/' . $grade->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view active grades (public endpoint).
     * Verifies the /active route returns only active grades.
     */
    public function test_anyone_can_view_active_grades(): void
    {
        // Create 3 active grades and 2 inactive grades
        Grade::factory()->active()->count(3)->create();
        Grade::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/grades/active');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 4: Anyone can view the sections of a grade (public endpoint).
     * Creates a grade and three sections, then checks the endpoint.
     * Sections are created manually to avoid duplicate name conflicts.
     */
    public function test_anyone_can_view_grade_sections(): void
    {
        $grade = Grade::factory()->create();

        /**  Create sections manually to guarantee uniqueness of (grade_id, name)
        */
        
        Section::create(['grade_id' => $grade->id, 'name' => 'A', 'is_active' => true]);
        Section::create(['grade_id' => $grade->id, 'name' => 'B', 'is_active' => true]);
        Section::create(['grade_id' => $grade->id, 'name' => 'C', 'is_active' => true]);

        $response = $this->getJson('/api/grades/' . $grade->id . '/sections');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 5: Only admin can create a grade (protected endpoint).
     * Verifies that:
     * - Unauthenticated request returns 401.
     * - Regular user request returns 403 (Forbidden).
     * - Admin request returns 201 (Created) and grade is stored.
     */
    public function test_only_admin_can_create_a_grade(): void
    {
        $gradeData = [
            'name' => 'Grade 8',
            'level' => 8,
            'is_active' => true,
        ];

        // 1) No authentication → 401 Unauthorized
        $response = $this->postJson('/api/grades', $gradeData);
        $response->assertStatus(401);

        // 2) Regular user → 403 Forbidden
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->postJson('/api/grades', $gradeData);
        $response->assertStatus(403);

        // 3) Admin → 201 Created and grade persisted
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/grades', $gradeData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Grade 8',
                         'level' => 8,
                         'is_active' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('grades', [
            'name' => 'Grade 8',
            'level' => 8,
        ]);
    }

    /**
     * Test 6: Only admin can update a grade.
     * Ensures that a regular user cannot modify a grade, while admin can.
     */
    public function test_only_admin_can_update_a_grade(): void
    {
        $grade = Grade::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Grade Name'];

        // Regular user → 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->putJson('/api/grades/' . $grade->id, $updateData);
        $response->assertStatus(403);

        // Admin → 200 and database updated
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/grades/' . $grade->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $grade->id,
                         'name' => 'Updated Grade Name',
                     ]
                 ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'name' => 'Updated Grade Name',
        ]);
    }

    /** ======
     * Test 7: Only admin can delete a grade.
     * Regular user cannot delete; admin can.
     */
    public function test_only_admin_can_delete_a_grade(): void
    {
        $grade = Grade::factory()->create();

        // Regular user → 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->deleteJson('/api/grades/' . $grade->id);
        $response->assertStatus(403);

        /**
         *   Admin → 200 and grade removed
         * */

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/grades/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
    }

    /**
     * Test 8: Cannot delete a grade that has associated sections.
     * This enforces business rule: a grade with sections should not be deletable.
     */
    public function test_cannot_delete_grade_that_has_sections(): void
    {
        $grade = Grade::factory()->create();

        /**  Create two sections for this grade
         * */

        Section::create(['grade_id' => $grade->id, 'name' => 'A', 'is_active' => true]);
        Section::create(['grade_id' => $grade->id, 'name' => 'B', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/grades/' . $grade->id);
        $response->assertStatus(422)
                 ->assertJson(['success' => false]);

        /**
         * Grade should still exist
         * */

        $this->assertDatabaseHas('grades', ['id' => $grade->id]);
    }

    /**
     * Test 9: Validation – grade name is required when creating.
     */
    public function test_grade_requires_name(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/grades', ['level' => 9]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 10: Validation – grade name must be unique.
     */
    public function test_grade_name_must_be_unique(): void
    {
        Grade::factory()->create(['name' => 'Grade 9']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/grades', [
                             'name' => 'Grade 9',
                             'level' => 9,
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 11: Filter grades by level.
     * Checks that the API supports filtering by the "level" query parameter.
     */
    public function test_filter_grades_by_level(): void
    {
        Grade::factory()->level(9)->count(2)->create();
        Grade::factory()->level(10)->count(3)->create();

        $response = $this->getJson('/api/grades?level=9');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 12: Search grades by name.
     * Verifies that the API can search grades using the "search" parameter.
     */
    public function test_search_grades_by_name(): void
    {
        Grade::factory()->create(['name' => 'Grade 9 Science']);
        Grade::factory()->create(['name' => 'Grade 9 Math']);
        Grade::factory()->create(['name' => 'Grade 10 Science']);

        $response = $this->getJson('/api/grades?search=Science');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 13: Pagination works.
     * When "per_page" is provided, the response should respect the limit.
     */
    public function test_grades_pagination(): void
    {
        Grade::factory()->count(25)->create();

        $response = $this->getJson('/api/grades?per_page=10');
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
     * Test 14: Viewing a non-existent grade returns 404.
     */
    public function test_view_non_existent_grade_returns_404(): void
    {
        $response = $this->getJson('/api/grades/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Grade not found',
                 ]);
    }
}
