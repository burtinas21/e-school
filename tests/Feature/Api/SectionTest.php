<?php

namespace Tests\Feature\Api;

use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Admin user
        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'email'   => 'admin_' . uniqid() . '@test.com',
        ]);

        // Regular user (student)
        $this->regularUser = User::factory()->create([
            'role_id' => 3,
            'email'   => 'user_' . uniqid() . '@test.com',
        ]);
    }

    /* ==========================================================================
     * SECTION 1: READ ENDPOINTS (anyone can access)
     * ========================================================================== */

    /**
     * Test 1: Anyone can list sections (public endpoint).
     */
    #[Test]
    public function anyone_can_list_sections(): void
    {
        Section::factory()->count(3)->create();

        // Unauthenticated
        $response = $this->getJson('/api/sections');
        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'success',
                     'data' => [['id', 'grade_id', 'name', 'is_active']]
                 ]);

        // Authenticated regular user
        $this->actingAs($this->regularUser, 'sanctum')
             ->getJson('/api/sections')
             ->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single section (public endpoint).
     */
    #[Test]
    public function anyone_can_view_a_single_section(): void
    {
        $section = Section::factory()->create();

        // Unauthenticated
        $this->getJson('/api/sections/' . $section->id)
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'id'   => $section->id,
                     'name' => $section->name,
                 ]
             ]);

        // Authenticated regular user
        $this->actingAs($this->regularUser, 'sanctum')
             ->getJson('/api/sections/' . $section->id)
             ->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view active sections only (public endpoint).
     */
    #[Test]
    public function anyone_can_view_active_sections(): void
    {
        Section::factory()->active()->count(3)->create();
        Section::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/sections/active');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 4: Anyone can view sections by grade (public endpoint).
     */
    #[Test]
    public function anyone_can_view_sections_by_grade(): void
    {
        $grade = Grade::factory()->create();
        Section::factory()->create(['grade_id' => $grade->id, 'name' => 'A', 'is_active' => true]);
        Section::factory()->create(['grade_id' => $grade->id, 'name' => 'B', 'is_active' => true]);
        Section::factory()->create(); // different grade

        $response = $this->getJson('/api/sections/by-grade/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 5: View sections by grade returns 404 when grade not found.
     */
    #[Test]
    public function view_sections_by_grade_returns_404_if_grade_not_found(): void
    {
        $response = $this->getJson('/api/sections/by-grade/99999');
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Grade not found']);
    }

    /* ==========================================================================
     * SECTION 2: WRITE ENDPOINTS (admin only)
     * ========================================================================== */

    /**
     * Test 6: Only admin can create a section (protected endpoint).
     */
    #[Test]
    public function only_admin_can_create_a_section(): void
    {
        $grade = Grade::factory()->create();
        $sectionData = [
            'grade_id' => $grade->id,
            'name'     => 'A',
            'is_active'=> true,
        ];

        // Unauthenticated
        $this->postJson('/api/sections', $sectionData)->assertStatus(401);

        // Regular user
        $this->actingAs($this->regularUser, 'sanctum')
             ->postJson('/api/sections', $sectionData)
             ->assertStatus(403);

        // Admin
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', $sectionData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Section created successfully',
                     'data' => [
                         'grade_id' => $grade->id,
                         'name'     => 'A',
                         'is_active'=> true,
                     ]
                 ]);

        $this->assertDatabaseHas('sections', [
            'grade_id' => $grade->id,
            'name'     => 'A',
        ]);
    }

    /**
     * Test 7: Only admin can update a section (protected endpoint).
     */
    #[Test]
    public function only_admin_can_update_a_section(): void
    {
        $section = Section::factory()->create(['name' => 'Old']);
        $updateData = ['name' => 'New'];

        // Regular user
        $this->actingAs($this->regularUser, 'sanctum')
             ->putJson('/api/sections/' . $section->id, $updateData)
             ->assertStatus(403);

        // Admin
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/sections/' . $section->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Section updated successfully',
                     'data' => [
                         'id'   => $section->id,
                         'name' => 'New',
                     ]
                 ]);

        $this->assertDatabaseHas('sections', [
            'id'   => $section->id,
            'name' => 'New',
        ]);
    }

    /**
     * Test 8: Only admin can delete a section (protected endpoint).
     */
    #[Test]
    public function only_admin_can_delete_a_section(): void
    {
        $section = Section::factory()->create();

        // Regular user
        $this->actingAs($this->regularUser, 'sanctum')
             ->deleteJson('/api/sections/' . $section->id)
             ->assertStatus(403);

        // Admin
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/sections/' . $section->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'message' => 'Section deleted successfully']);

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    /**
     * Test 9: Cannot delete a section that has students (business rule).
     */
    #[Test]
    public function cannot_delete_section_that_has_students(): void
    {
        $section = Section::factory()->create();
        Student::factory()->create(['section_id' => $section->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/sections/' . $section->id);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Cannot delete section because it has students. Remove students first.',
                 ]);

        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    /* ==========================================================================
     * SECTION 3: VALIDATION
     * ========================================================================== */

    /**
     * Test 10: Validation – name is required when creating a section.
     */
    #[Test]
    public function section_requires_name(): void
    {
        $grade = Grade::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', ['grade_id' => $grade->id]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 11: Section name must be unique within the same grade (custom error).
     */
    #[Test]
    public function section_name_must_be_unique_within_grade(): void
    {
        $grade = Grade::factory()->create();
        Section::factory()->create(['grade_id' => $grade->id, 'name' => 'A']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/sections', [
                             'grade_id' => $grade->id,
                             'name' => 'A',
                         ]);
        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Section already exists in this grade',
                 ]);
    }

    /**
     * Test 12: Viewing a non‑existent section returns 404.
     */
    #[Test]
    public function view_non_existent_section_returns_404(): void
    {
        $response = $this->getJson('/api/sections/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Section not found',
                 ]);
    }
}
