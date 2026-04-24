<?php

namespace Tests\Feature\Api;

use App\Models\Guardian;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $teacherUser;
    protected $parentUser;
    protected $studentUser;
    protected $teacher;
    protected $guardian;
    protected $student;

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

        // Teacher user
        $this->teacherUser = User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'is_active' => true,
        ]);
        $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);

        // Parent user
        $this->parentUser = User::factory()->create([
            'name' => 'Parent User',
            'email' => 'parent_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'is_active' => true,
        ]);
        $this->guardian = Guardian::factory()->create(['user_id' => $this->parentUser->id]);

        // Student user (for the parent)
        $this->studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }

    /**
     * Test 1: Unauthenticated user cannot list guardians.
     */
    public function test_unauthenticated_user_cannot_list_guardians()
    {
        $response = $this->getJson('/api/guardians');
        $response->assertStatus(401);
    }

    /**
     * Test 2: Unauthenticated user cannot view a guardian.
     */
    public function test_unauthenticated_user_cannot_view_guardian()
    {
        $response = $this->getJson('/api/guardians/' . $this->guardian->id);
        $response->assertStatus(401);
    }

    /**
     * Test 3: Admin can list all guardians.
     */
    public function test_admin_can_list_all_guardians()
    {
        Guardian::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/guardians');
        $response->assertStatus(200)
                 ->assertJsonCount(4, 'data.data'); // 3 new + 1 from setUp
    }

    /**
     * Test 4: Admin can view any guardian.
     */
    public function test_admin_can_view_any_guardian()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/guardians/' . $this->guardian->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['id' => $this->guardian->id],
                 ]);
    }

    /**
     * Test 5: Teacher sees only guardians of students in assigned sections.
     */
    public function test_teacher_sees_only_guardians_of_assigned_students()
    {
        $sectionA = Section::factory()->create(['name' => 'A']);
        $sectionB = Section::factory()->create(['name' => 'B']);

        // Assign teacher to sectionA
        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $sectionA->id,
        ]);

        // Create students with guardians in both sections
        $guardianA = Guardian::factory()->create();
        $guardianB = Guardian::factory()->create();

        Student::factory()->create(['section_id' => $sectionA->id, 'guardian_id' => $guardianA->id]);
        Student::factory()->create(['section_id' => $sectionB->id, 'guardian_id' => $guardianB->id]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/guardians');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['id' => $guardianA->id])
                 ->assertJsonMissing(['id' => $guardianB->id]);
    }

    /**
     * Test 6: Teacher can view a guardian only if they have a student in a section they teach.
     */
    public function test_teacher_can_view_guardian_only_if_teaches_their_childs_section()
    {
        $sectionA = Section::factory()->create(['name' => 'A']);
        $sectionB = Section::factory()->create(['name' => 'B']);

        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $sectionA->id,
        ]);

        $guardianA = Guardian::factory()->create();
        $guardianB = Guardian::factory()->create();

        Student::factory()->create(['section_id' => $sectionA->id, 'guardian_id' => $guardianA->id]);
        Student::factory()->create(['section_id' => $sectionB->id, 'guardian_id' => $guardianB->id]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/guardians/' . $guardianA->id);
        $response->assertStatus(200);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/guardians/' . $guardianB->id);
        $response->assertStatus(403);
    }

    /**
     * Test 7: Parent can view only their own guardian profile.
     */
    public function test_parent_can_view_only_their_own_profile()
    {
        $otherGuardian = Guardian::factory()->create();

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/guardians/' . $this->guardian->id);
        $response->assertStatus(200);

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/guardians/' . $otherGuardian->id);
        $response->assertStatus(403);
    }

    /**
     * Test 8: Parent list contains only themselves.
     */
    public function test_parent_list_contains_only_themselves()
    {
        Guardian::factory()->count(2)->create();

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/guardians');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['id' => $this->guardian->id]);
    }

    /**
     * Test 9: Student cannot list guardians.
     */
    public function test_student_cannot_list_guardians()
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/guardians');
        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data.data'); // empty result
    }

    /**
     * Test 10: Student cannot view a guardian.
     */
    public function test_student_cannot_view_guardian()
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/guardians/' . $this->guardian->id);
        $response->assertStatus(403); // privacy denied
    }

    /**
     * Test 11: Only admin can create a guardian.
     */
    public function test_only_admin_can_create_guardian()
    {
        $data = [
            'name' => 'New Guardian',
            'email' => 'guardian@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'occupation' => 'Engineer',
            'relationship' => 'Father',
            'receive_notifications' => true,
        ];

        // Unauthenticated -> 401
        $response = $this->postJson('/api/guardians', $data);
        $response->assertStatus(401);

        // Teacher -> 403
        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/guardians', $data);
        $response->assertStatus(403);

        // Student -> 403
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->postJson('/api/guardians', $data);
        $response->assertStatus(403);

        // Parent -> 403
        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->postJson('/api/guardians', $data);
        $response->assertStatus(403);

        // Admin -> 201
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/guardians', $data);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'email' => 'guardian@test.com',
                             'name' => 'New Guardian',
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('guardians', [
            'occupation' => 'Engineer',
            'relationship' => 'Father',
        ]);
    }

    /**
     * Test 12: Only admin can update a guardian.
     */
    public function test_only_admin_can_update_guardian()
    {
        $guardian = Guardian::factory()->create();
        $updateData = ['occupation' => 'Doctor'];

        // Teacher -> 403
        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->putJson('/api/guardians/' . $guardian->id, $updateData);
        $response->assertStatus(403);

        // Student -> 403
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->putJson('/api/guardians/' . $guardian->id, $updateData);
        $response->assertStatus(403);

        // Parent -> 403
        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->putJson('/api/guardians/' . $guardian->id, $updateData);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/guardians/' . $guardian->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $guardian->id,
                         'occupation' => 'Doctor',
                     ],
                 ]);

        $this->assertDatabaseHas('guardians', [
            'id' => $guardian->id,
            'occupation' => 'Doctor',
        ]);
    }

    /**
     * Test 13: Only admin can delete a guardian.
     */
    public function test_only_admin_can_delete_guardian()
    {
        $guardian = Guardian::factory()->create();

        // Teacher -> 403
        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->deleteJson('/api/guardians/' . $guardian->id);
        $response->assertStatus(403);

        // Student -> 403
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->deleteJson('/api/guardians/' . $guardian->id);
        $response->assertStatus(403);

        // Parent -> 403
        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->deleteJson('/api/guardians/' . $guardian->id);
        $response->assertStatus(403);

        // Admin -> 200
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/guardians/' . $guardian->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('guardians', ['id' => $guardian->id]);
    }

    /**
     * Test 14: Validation – name is required.
     */
    public function test_guardian_requires_name()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/guardians', [
                             'email' => 'test@test.com',
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 15: Validation – email must be unique.
     */
    public function test_guardian_email_must_be_unique()
    {
        $existing = Guardian::factory()->create();
        $existingEmail = $existing->user->email;

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/guardians', [
                             'name' => 'Test',
                             'email' => $existingEmail,
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 16: Filter guardians by name (search).
     */
    public function test_filter_guardians_by_name()
    {
        Guardian::factory()->create(['user_id' => User::factory()->create(['name' => 'John Doe'])->id]);
        Guardian::factory()->create(['user_id' => User::factory()->create(['name' => 'Jane Smith'])->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/guardians?search=John');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['name' => 'John Doe']);
    }

    /**
     * Test 17: Pagination works.
     */
    public function test_guardians_pagination()
    {
        Guardian::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/guardians?per_page=10');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'current_page',
                         'data',
                         'per_page',
                         'total',
                     ],
                 ]);

        $this->assertEquals(10, $response['data']['per_page']);
    }

    /**
     * Test 18: View non‑existent guardian returns 404.
     */
    public function test_view_non_existent_guardian_returns_404()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/guardians/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Guardian not found',
                 ]);
    }
}
