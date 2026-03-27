<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Guardian;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $teacherUser;
    protected $studentUser;
    protected $parentUser;
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

        // Student user
        $this->studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);

        // Parent user
        $this->parentUser = User::factory()->create([
            'name' => 'Parent User',
            'email' => 'parent_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'is_active' => true,
        ]);
        $this->guardian = Guardian::factory()->create(['user_id' => $this->parentUser->id]);

        // Create a student for the student and parent (linked to the guardian)
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }

    /**
     * Test 1: Unauthenticated user cannot list students.
     */
    public function test_unauthenticated_user_cannot_list_students()
    {
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);
    }

    /**
     * Test 2: Unauthenticated user cannot view a specific student.
     */
    public function test_unauthenticated_user_cannot_view_a_student()
    {
        $response = $this->getJson('/api/students/' . $this->student->id);
        $response->assertStatus(401);
    }

    /**
     * Test 3: Admin can list all students.
     */
    public function test_admin_can_list_all_students()
    {
        Student::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students');
        $response->assertStatus(200)
                 ->assertJsonCount(4, 'data.data'); // 3 new + 1 from setUp
    }

    /**
     * Test 4: Admin can view any student.
     */
    public function test_admin_can_view_any_student()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students/' . $this->student->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['id' => $this->student->id],
                 ]);
    }

    /**
     * Test 5: Teacher sees only students in assigned sections.
     */
    public function test_teacher_sees_only_students_in_assigned_sections()
    {
        $sectionA = Section::factory()->create(['name' => 'A']);
        $sectionB = Section::factory()->create(['name' => 'B']);

        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $sectionA->id,
        ]);

        $studentInA = Student::factory()->create(['section_id' => $sectionA->id]);
        $studentInB = Student::factory()->create(['section_id' => $sectionB->id]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/students');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['id' => $studentInA->id])
                 ->assertJsonMissing(['id' => $studentInB->id]);
    }

    /**
     * Test 6: Teacher can view a student only if assigned to that student's section.
     */
    public function test_teacher_can_view_student_only_if_assigned_to_their_section()
    {
        $sectionA = Section::factory()->create(['name' => 'A']);
        $sectionB = Section::factory()->create(['name' => 'B']);

        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $sectionA->id,
        ]);

        $studentInA = Student::factory()->create(['section_id' => $sectionA->id]);
        $studentInB = Student::factory()->create(['section_id' => $sectionB->id]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/students/' . $studentInA->id);
        $response->assertStatus(200);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/students/' . $studentInB->id);
        $response->assertStatus(403);
    }

    /**
     * Test 7: Student can view only their own profile.
     */
    public function test_student_can_view_only_their_own_profile()
    {
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/students/' . $this->student->id);
        $response->assertStatus(200);

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/students/' . $otherStudent->id);
        $response->assertStatus(403);
    }

    /**
     * Test 8: Student list contains only themselves.
     */
    public function test_student_list_contains_only_themselves()
    {
        Student::factory()->count(2)->create();

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/students');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['id' => $this->student->id]);
    }

    /**
     * Test 9: Parent sees only their own children.
     */
    public function test_parent_sees_only_their_own_children()
    {
        $otherGuardian = Guardian::factory()->create();
        $otherStudent = Student::factory()->create(['guardian_id' => $otherGuardian->id]);

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/students');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonFragment(['id' => $this->student->id])
                 ->assertJsonMissing(['id' => $otherStudent->id]);
    }

    /**
     * Test 10: Parent can view only their own children.
     */
    public function test_parent_can_view_only_their_own_children()
    {
        $otherGuardian = Guardian::factory()->create();
        $otherStudent = Student::factory()->create(['guardian_id' => $otherGuardian->id]);

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/students/' . $this->student->id);
        $response->assertStatus(200);

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/students/' . $otherStudent->id);
        $response->assertStatus(403);
    }

    /**
     * Test 11: Admin can create a student.
     */
    public function test_admin_can_create_student()
    {
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $data = [
            'name' => 'New Student',
            'email' => 'new@student.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'addmission_number' => 'STU999',
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/students', $data);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => ['addmission_number' => 'STU999'],
                 ]);

        $this->assertDatabaseHas('students', [
            'addmission_number' => 'STU999',
        ]);
    }

    /**
     * Test 12: Admin can update a student.
     */
    public function test_admin_can_update_student()
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/students/' . $student->id, [
                             'addmission_number' => 'UPDATED',
                         ]);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $student->id,
                         'addmission_number' => 'UPDATED',
                     ],
                 ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'addmission_number' => 'UPDATED',
        ]);
    }

    /**
     * Test 13: Admin can delete a student.
     */
    public function test_admin_can_delete_student()
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/students/' . $student->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    /**
     * Test 14: Teacher cannot create a student.
     */
    public function test_teacher_cannot_create_student()
    {
        $data = [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/students', $data);
        $response->assertStatus(403);
    }

    /**
     * Test 15: Student cannot create a student.
     */
    public function test_student_cannot_create_student()
    {
        $data = [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->postJson('/api/students', $data);
        $response->assertStatus(403);
    }

    /**
     * Test 16: Parent cannot create a student.
     */
    public function test_parent_cannot_create_student()
    {
        $data = [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->postJson('/api/students', $data);
        $response->assertStatus(403);
    }

    /**
     * Test 17: Validation – student requires a name.
     */
    public function test_student_requires_name()
    {
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/students', [
                             'email' => 'test@test.com',
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                             'addmission_number' => 'STU123',
                             'grade_id' => $grade->id,
                             'section_id' => $section->id,
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 18: Validation – admission number must be unique.
     */
    public function test_addmission_number_must_be_unique()
    {
        $existing = Student::factory()->create(['addmission_number' => 'DUPLICATE']);

        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/students', [
                             'name' => 'Test',
                             'email' => 'test@test.com',
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                             'addmission_number' => 'DUPLICATE',
                             'grade_id' => $grade->id,
                             'section_id' => $section->id,
                         ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['addmission_number']);
    }

    /**
     * Test 19: Filter students by grade (admin).
     */
    public function test_filter_students_by_grade()
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();

        Student::factory()->count(2)->create(['grade_id' => $grade1->id]);
        Student::factory()->count(3)->create(['grade_id' => $grade2->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students?grade_id=' . $grade1->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 20: Filter students by section (admin).
     */
    public function test_filter_students_by_section()
    {
        $section1 = Section::factory()->create();
        $section2 = Section::factory()->create();

        Student::factory()->count(2)->create(['section_id' => $section1->id]);
        Student::factory()->count(3)->create(['section_id' => $section2->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students?section_id=' . $section1->id);
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 21: Pagination works (admin).
     */
    public function test_students_pagination()
    {
        Student::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students?per_page=10');
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
     * Test 22: View non‑existent student returns 404.
     */
    public function test_view_non_existent_student_returns_404()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/students/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Student not found',
                 ]);
    }
}
