<?php

namespace Tests\Feature\Api;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    /**
     * Set up the test environment.
     * Creates roles, an admin user, and a regular user (student).
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles (assuming they are defined in a seeder or manually)
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Admin user (role_id 1)
        $this->adminUser = User::factory()->create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id'  => 1,
            'is_active'=> true,
        ]);

        // Regular user (student, role_id 3)
        $this->regularUser = User::factory()->create([
            'name'     => 'Regular User',
            'email'    => 'user_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id'  => 3,
            'is_active'=> true,
        ]);
    }

    /* ==========================================================================
     * READ ENDPOINTS (require authentication)
     * ========================================================================== */

    /**
     * Test 1: Authenticated user can list all teacher assignments.
     *
     * This test ensures that any authenticated user (not just admin) can retrieve
     * the list of teacher assignments. It verifies the response structure and
     * that the data contains the expected fields.
     */
    public function test_authenticated_user_can_list_assignments(): void
    {
        TeacherAssignment::factory()->count(3)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'teacher_id', 'subject_id', 'section_id', 'academic_year', 'is_primary']
                         ]
                     ]
                 ]);
    }

    /**
     * Test 2: Authenticated user can view a single assignment.
     *
     * This test verifies that a specific assignment can be retrieved by its ID,
     * and that the response contains the correct data.
     */
    public function test_authenticated_user_can_view_a_single_assignment(): void
    {
        $assignment = TeacherAssignment::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/' . $assignment->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id'            => $assignment->id,
                         'teacher_id'    => $assignment->teacher_id,
                         'subject_id'    => $assignment->subject_id,
                         'section_id'    => $assignment->section_id,
                         'academic_year' => $assignment->academic_year,
                     ]
                 ]);
    }

    /**
     * Test 3: Authenticated user can filter assignments by teacher (query parameter).
     *
     * This test confirms that the index endpoint supports filtering by teacher_id
     * using a query parameter, returning only assignments for that teacher.
     */
    public function test_authenticated_user_can_filter_by_teacher(): void
    {
        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();

        TeacherAssignment::factory()->create(['teacher_id' => $teacher1->id]);
        TeacherAssignment::factory()->create(['teacher_id' => $teacher1->id]);
        TeacherAssignment::factory()->create(['teacher_id' => $teacher2->id]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments?teacher_id=' . $teacher1->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 4: Authenticated user can get assignments by teacher (dedicated endpoint).
     *
     * This test uses the /by-teacher/{teacher} endpoint to retrieve all assignments
     * for a given teacher. It asserts the correct count and structure.
     */
    public function test_authenticated_user_can_get_assignments_by_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        TeacherAssignment::factory()->count(2)->create(['teacher_id' => $teacher->id]);
        TeacherAssignment::factory()->create(); // different teacher

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/by-teacher/' . $teacher->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 5: Authenticated user can get assignments by section.
     *
     * This test checks the /by-section/{section} endpoint, ensuring it returns
     * only assignments belonging to the specified section.
     */
    public function test_authenticated_user_can_get_assignments_by_section(): void
    {
        $section = Section::factory()->create();
        TeacherAssignment::factory()->count(2)->create(['section_id' => $section->id]);
        TeacherAssignment::factory()->create(); // different section

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/by-section/' . $section->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 6: Authenticated user can get assignments by subject.
     *
     * This test verifies the /by-subject/{subject} endpoint, which returns
     * assignments for a given subject.
     */
    public function test_authenticated_user_can_get_assignments_by_subject(): void
    {
        $subject = Subject::factory()->create();
        TeacherAssignment::factory()->count(2)->create(['subject_id' => $subject->id]);
        TeacherAssignment::factory()->create(); // different subject

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/by-subject/' . $subject->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 7: Authenticated user can get assignments by grade.
     *
     * This test uses the /by-grade/{grade} endpoint, which retrieves assignments
     * for all sections that belong to a given grade.
     */
    public function test_authenticated_user_can_get_assignments_by_grade(): void
    {
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        TeacherAssignment::factory()->count(2)->create(['section_id' => $section->id]);
        TeacherAssignment::factory()->create(); // different grade

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/by-grade/' . $grade->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /* ==========================================================================
     * WRITE ENDPOINTS (require admin role)
     * ========================================================================== */

    /**
     * Test 8: Only admin can create a teacher assignment.
     *
     * This test ensures that:
     * - Unauthenticated request returns 401.
     * - Regular authenticated user (student) returns 403.
     * - Admin user successfully creates the assignment (201) and the record is in the database.
     */
    public function test_only_admin_can_create_an_assignment(): void
    {
        // Prepare valid data (subject and section must share the same grade)
        $grade = Grade::factory()->create();
        $teacher = Teacher::factory()->create();
        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $assignmentData = [
            'teacher_id'    => $teacher->id,
            'subject_id'    => $subject->id,
            'section_id'    => $section->id,
            'academic_year' => now()->year,
            'is_primary'    => true,
        ];

        // Unauthenticated -> 401
        $this->postJson('/api/teacher-assignments', $assignmentData)
             ->assertStatus(401);

        // Regular user -> 403
        $this->actingAs($this->regularUser, 'sanctum')
             ->postJson('/api/teacher-assignments', $assignmentData)
             ->assertStatus(403);

        // Admin -> 201
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/teacher-assignments', $assignmentData)
             ->assertStatus(201)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'teacher_id'    => $teacher->id,
                     'subject_id'    => $subject->id,
                     'section_id'    => $section->id,
                     'academic_year' => now()->year,
                     'is_primary'    => true,
                 ]
             ]);

        $this->assertDatabaseHas('teacher_assignments', [
            'teacher_id'    => $teacher->id,
            'subject_id'    => $subject->id,
            'section_id'    => $section->id,
        ]);

        // Logic Upgrade Verification: Auto-Notify Teacher
        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $teacher->id,
            'type'       => 'event',
        ]);
    }

    /**
     * Test 9: Cannot create duplicate assignment (unique constraint).
     *
     * This test verifies that attempting to create an assignment with the same
     * teacher, subject, section, and academic year results in a 422 response
     * with an appropriate error message.
     */
    public function test_cannot_create_duplicate_assignment(): void
    {
        $grade = Grade::factory()->create();
        $teacher = Teacher::factory()->create();
        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $assignmentData = [
            'teacher_id'    => $teacher->id,
            'subject_id'    => $subject->id,
            'section_id'    => $section->id,
            'academic_year' => now()->year,
            'is_primary'    => true,
        ];

        // First creation succeeds
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/teacher-assignments', $assignmentData)
             ->assertStatus(201);

        // Second attempt fails with 422
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/teacher-assignments', $assignmentData)
             ->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'This teacher is already assigned to this subject and section for the academic year',
             ]);
    }

    /**
     * Test 10: Only admin can update a teacher assignment.
     *
     * This test ensures that a regular user cannot update an assignment,
     * while an admin can successfully update it and the changes are reflected in the database.
     */
    public function test_only_admin_can_update_an_assignment(): void
    {
        $assignment = TeacherAssignment::factory()->create();
        $updateData = ['is_primary' => false];

        // Regular user -> 403
        $this->actingAs($this->regularUser, 'sanctum')
             ->putJson('/api/teacher-assignments/' . $assignment->id, $updateData)
             ->assertStatus(403);

        // Admin -> 200
        $this->actingAs($this->adminUser, 'sanctum')
             ->putJson('/api/teacher-assignments/' . $assignment->id, $updateData)
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'id'         => $assignment->id,
                     'is_primary' => false,
                 ]
             ]);

        $this->assertDatabaseHas('teacher_assignments', [
            'id'         => $assignment->id,
            'is_primary' => false,
        ]);
    }

    /**
     * Test 11: Only admin can delete a teacher assignment.
     *
     * This test ensures that a regular user cannot delete an assignment,
     * while an admin can successfully delete it and the record is removed from the database.
     */
    public function test_only_admin_can_delete_an_assignment(): void
    {
        $assignment = TeacherAssignment::factory()->create();

        // Regular user -> 403
        $this->actingAs($this->regularUser, 'sanctum')
             ->deleteJson('/api/teacher-assignments/' . $assignment->id)
             ->assertStatus(403);

        // Admin -> 200
        $this->actingAs($this->adminUser, 'sanctum')
             ->deleteJson('/api/teacher-assignments/' . $assignment->id)
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('teacher_assignments', ['id' => $assignment->id]);
    }

    /**
     * Test 12: Bulk store creates multiple assignments (admin only).
     *
     * This test sends an array of two distinct assignments to the bulk store endpoint.
     * It ensures both are created successfully, the response message matches the count,
     * and exactly two records exist in the database.
     */
    public function test_bulk_store_assignments(): void
    {
        $grade = Grade::factory()->create();
        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();
        $subject1 = Subject::factory()->create(['grade_id' => $grade->id]);
        $subject2 = Subject::factory()->create(['grade_id' => $grade->id]);
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $assignments = [
            [
                'teacher_id'    => $teacher1->id,
                'subject_id'    => $subject1->id,
                'section_id'    => $section->id,
                'academic_year' => now()->year,
                'is_primary'    => true,
            ],
            [
                'teacher_id'    => $teacher2->id,
                'subject_id'    => $subject2->id,
                'section_id'    => $section->id,
                'academic_year' => now()->year,
                'is_primary'    => false,
            ],
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/teacher-assignments/bulk', ['assignments' => $assignments]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '2 assignments created successfully',
                 ]);

        $this->assertDatabaseCount('teacher_assignments', 2);
    }

    /* ==========================================================================
     * NEGATIVE TESTS (404, 401, etc.)
     * ========================================================================== */

    /**
     * Test 13: Viewing a non‑existent assignment returns 404.
     *
     * This test ensures that when a request is made for an assignment ID that does not exist,
     * the API responds with a 404 status and a proper error message.
     */
    public function test_view_non_existent_assignment_returns_404(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/teacher-assignments/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Teacher assignment not found',
                 ]);
    }

    /**
     * Test 14: Unauthenticated users cannot access read endpoints.
     *
     * This test verifies that any request to a read endpoint without authentication
     * receives a 401 Unauthorized response.
     */
    public function test_unauthenticated_cannot_access_read_endpoints(): void
    {
        TeacherAssignment::factory()->create();

        $this->getJson('/api/teacher-assignments')
             ->assertStatus(401);

        $this->getJson('/api/teacher-assignments/1')
             ->assertStatus(401);

        $this->getJson('/api/teacher-assignments/by-teacher/1')
             ->assertStatus(401);
    }
}
