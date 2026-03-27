<?php

namespace Tests\Feature\Api;

use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent any seeding – we will create data manually
        $this->seed = false;

        // Create roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);

        // Create regular user (student)
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Anyone can list all sections (public endpoint)
     */
    public function test_anyone_can_list_sections(): void
    {
        // Create some sections (they will be tied to automatically created grades)
        Section::factory()->count(3)->create();

        // Unauthenticated request
        $response = $this->getJson('/api/sections');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'grade_id', 'name', 'is_active']
                         ]
                     ]
                 ]);

        // Authenticated request (regular user)
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/sections');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single section
     */
    public function test_anyone_can_view_a_single_section(): void
    {
        $section = Section::factory()->create();

        // Without authentication
        $response = $this->getJson('/api/sections/' . $section->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $section->id,
                         'name' => $section->name,
                     ]
                 ]);

        // With authentication
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/sections/' . $section->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view active sections only
     */
    public function test_anyone_can_view_active_sections(): void
    {
        Section::factory()->active()->count(3)->create();
        Section::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/sections/active');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 4: Anyone can view sections by grade
     */
    public function test_anyone_can_view_sections_by_grade(): void
    {
        $grade = Grade::factory()->create();
        Section::factory()->count(2)->create(['grade_id' => $grade->id]);
        // Create a section in another grade to ensure filtering works
        Section::factory()->create();

        $response = $this->getJson('/api/sections/by-grade/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 5: Only admin can create a section
     */
    public function test_only_admin_can_create_a_section(): void
    {
        $grade = Grade::factory()->create();
        $sectionData = [
            'grade_id' => $grade->id,
            'name' => 'A',
            'is_active' => true,
        ];

        // Without authentication – 401
        $response = $this->postJson('/api/sections', $sectionData);
        $response->assertStatus(401);

        // Regular user – 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->postJson('/api/sections', $sectionData);
        $response->assertStatus(403);

        // Admin – 201
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', $sectionData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'grade_id' => $grade->id,
                         'name' => 'A',
                         'is_active' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('sections', [
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
    }

    /**
     * Test 6: Only admin can update a section
     */
    public function test_only_admin_can_update_a_section(): void
    {
        $section = Section::factory()->create(['name' => 'Old']);
        $updateData = ['name' => 'New'];

        // Regular user – 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->putJson('/api/sections/' . $section->id, $updateData);
        $response->assertStatus(403);

        // Admin – 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/sections/' . $section->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $section->id,
                         'name' => 'New',
                     ]
                 ]);

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'name' => 'New',
        ]);
    }

    /**
     * Test 7: Only admin can delete a section
     */
    public function test_only_admin_can_delete_a_section(): void
    {
        $section = Section::factory()->create();

        // Regular user – 403
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->deleteJson('/api/sections/' . $section->id);
        $response->assertStatus(403);

        // Admin – 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/sections/' . $section->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    /**
     * Test 8: Cannot delete a section that has students (if students exist)
     * (This test assumes a student belongs to a section. We'll create a student linked to the section.)
     */
    public function test_cannot_delete_section_that_has_students(): void
    {
        $section = Section::factory()->create();

        // Create a student belonging to this section
        $student = \App\Models\Student::factory()->create([
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/sections/' . $section->id);
        $response->assertStatus(422)
                 ->assertJson(['success' => false]);

        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    /**
     * Test 9: Validation – section name is required
     */
    public function test_section_requires_name(): void
    {
        $grade = Grade::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', ['grade_id' => $grade->id]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 10: Section name must be unique within the same grade
     */
    public function test_section_name_must_be_unique_within_grade(): void
    {
        $grade = Grade::factory()->create();
        Section::factory()->create(['grade_id' => $grade->id, 'name' => 'A']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', [
                             'grade_id' => $grade->id,
                             'name' => 'A',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 11: Filter sections by grade (using query parameter)
     */
    public function test_filter_sections_by_grade(): void
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();

        Section::factory()->count(2)->create(['grade_id' => $grade1->id]);
        Section::factory()->count(3)->create(['grade_id' => $grade2->id]);

        $response = $this->getJson('/api/sections?grade_id=' . $grade1->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 12: Pagination works
     */
    public function test_sections_pagination(): void
    {
        Section::factory()->count(25)->create();

        $response = $this->getJson('/api/sections?per_page=10');
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
     * Test 13: View non-existent section returns 404
     */
    public function test_view_non_existent_section_returns_404(): void
    {
        $response = $this->getJson('/api/sections/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Section not found',
                 ]);
    }
}
