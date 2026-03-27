<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Period;
use App\Models\TeacherAssignment;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $teacherUser;
    protected $studentUser;
    protected $parentUser;
    protected $teacher;
    protected $student;
    protected $subject;
    protected $section;
    protected $period;
    protected $grade;

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

        // Grade and section
        $this->grade = Grade::factory()->create();
        $this->section = Section::factory()->create(['grade_id' => $this->grade->id]);

        // Subject belonging to the grade
        $this->subject = Subject::factory()->create(['grade_id' => $this->grade->id]);

        // Period
        $this->period = Period::factory()->create();

        // Teacher assignment – teacher teaches this subject in this section
        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
        ]);

        // Student user and student record
        $this->studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
        ]);

        // Parent user and guardian linked to student
        $this->parentUser = User::factory()->create([
            'name' => 'Parent User',
            'email' => 'parent_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => 4,
            'is_active' => true,
        ]);
        $guardian = \App\Models\Guardian::factory()->create(['user_id' => $this->parentUser->id]);
        $this->student->guardian_id = $guardian->id;
        $this->student->save();
    }

    /**
     * Test 1: Unauthenticated user cannot mark attendance.
     */
    public function test_unauthenticated_user_cannot_mark_attendance()
    {
        $data = [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'period_id' => $this->period->id,
            'attendances' => [
                ['student_id' => $this->student->id, 'status' => 'present'],
            ],
        ];

        $response = $this->postJson('/api/attendances/mark', $data);
        $response->assertStatus(401);
    }

    /**
     * Test 2: Teacher can mark attendance.
     */
    public function test_teacher_can_mark_attendance()
    {
        $data = [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'period_id' => $this->period->id,
            'attendances' => [
            ['student_id' => $this->student->id, 'status' => 'present'],
            ],
        ];

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/mark', $data);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '1 attendance records saved successfully',
                 ]);

        $this->assertDatabaseHas('attendances', [
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString() . ' 00:00:00',
            'status' => 'present',
        ]);
    }

    /**
     * Test 3: Teacher cannot mark attendance for a section they are not assigned to.
     */
    public function test_teacher_cannot_mark_attendance_for_unassigned_section()
    {
        // Create another section, subject, teacher assignment not linking to this teacher
        $otherSection = Section::factory()->create();
        $otherSubject = Subject::factory()->create(['grade_id' => $otherSection->grade_id]);
        $otherStudent = Student::factory()->create(['section_id' => $otherSection->id]);

        $data = [
            'grade_id' => $otherSection->grade_id,
            'section_id' => $otherSection->id,
            'subject_id' => $otherSubject->id,
            'date' => now()->toDateString(),
            'period_id' => $this->period->id,
            'attendances' => [
                ['student_id' => $otherStudent->id, 'status' => 'present'],
            ],
        ];

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/mark', $data);
        $response->assertStatus(403); // Unauthorized
    }

    /**
     * Test 4: Teacher can get class attendance.
     */
    public function test_teacher_can_get_class_attendance()
    {
        // Create some attendance records
        Attendance::factory()->create([
            'grade_id' =>$this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/class', [
                             'grade_id' => $this->grade->id,
                             'section_id' => $this->section->id,
                             'subject_id' => $this->subject->id,
                             'date' => now()->toDateString(),
                             'period_id' => $this->period->id,
                         ]);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         [
                             'student_id' => $this->student->id,
                             'student_name' => $this->student->user->name,
                             'status' => 'present',
                         ]
                     ]
                 ]);
    }

    /**
     * Test 5: Student can view their own attendance history.
     */
    public function test_student_can_view_own_attendance_history()
    {
        Attendance::factory()->create([
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/attendances/student/' . $this->student->id . '/history');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Test 6: Student cannot view another student's history.
     */
    public function test_student_cannot_view_other_student_history()
    {
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/attendances/student/' . $otherStudent->id . '/history');
        $response->assertStatus(403);
    }

    /**
     * Test 7: Parent can view their child's attendance history.
     */
    public function test_parent_can_view_child_attendance_history()
    {
        Attendance::factory()->create([
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/attendances/student/' . $this->student->id . '/history');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Test 8: Parent cannot view other student's history.
     */
    public function test_parent_cannot_view_other_student_history()
    {
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->getJson('/api/attendances/student/' . $otherStudent->id . '/history');
        $response->assertStatus(403);
    }

    /**
     * Test 9: Admin can view any student's history.
     */
    public function test_admin_can_view_any_student_history()
    {
        Attendance::factory()->create([
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/attendances/student/' . $this->student->id . '/history');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Test 10: Admin can generate attendance report.
     */
    public function test_admin_can_generate_attendance_report()
    {
        Attendance::factory()->create([
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/attendances/report', [
                             'start_date' => now()->subDays(1)->toDateString(),
                             'end_date' => now()->addDays(1)->toDateString(),
                             'section_id' => $this->section->id,
                         ]);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'records',
                         'statistics',
                     ],
                 ]);
    }

    /**
     * Test 11: Teacher can generate report for their section.
     */
    public function test_teacher_can_generate_report_for_assigned_section()
    {
        Attendance::factory()->create([
            'grade_id' => $this->grade->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'period_id' => $this->period->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/report', [
                             'start_date' => now()->subDays(1)->toDateString(),
                             'end_date' => now()->addDays(1)->toDateString(),
                             'section_id' => $this->section->id,
                         ]);
        $response->assertStatus(200);
    }

    /**
     * Test 12: Teacher cannot generate report for another section.
     */
    public function test_teacher_cannot_generate_report_for_other_section()
    {
        $otherSection = Section::factory()->create();

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/report', [
                             'start_date' => now()->subDays(1)->toDateString(),
                             'end_date' => now()->addDays(1)->toDateString(),
                             'section_id' => $otherSection->id,
                         ]);
        $response->assertStatus(403);
    }

    /**
     * Test 13: Student cannot generate report.
     */
    public function test_student_cannot_generate_report()
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->postJson('/api/attendances/report', [
                             'start_date' => now()->subDays(1)->toDateString(),
                             'end_date' => now()->addDays(1)->toDateString(),
                         ]);
        $response->assertStatus(403);
    }

    /**
     * Test 14: Parent cannot generate report.
     */
    public function test_guardian_cannot_generate_report()
    {
        $response = $this->actingAs($this->parentUser, 'sanctum')
                         ->postJson('/api/attendances/report', [
                             'start_date' => now()->subDays(1)->toDateString(),
                             'end_date' => now()->addDays(1)->toDateString(),
                         ]);
        $response->assertStatus(403);
    }

    /**
     * Test 15: Cannot mark attendance for a date in the future.
     */
    public function test_cannot_mark_attendance_for_future_date()
    {
        $futureDate = now()->addDays(1)->toDateString();

        $data = [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'date' => $futureDate,
            'period_id' => $this->period->id,
            'attendances' => [
                ['student_id' => $this->student->id, 'status' => 'present'],
            ],
        ];

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/mark', $data);
        $response->assertStatus(422); // validation error
    }

    /**
     * Test 16: Cannot mark duplicate attendance for the same student/subject/date/period.
     */
    public function test_cannot_mark_duplicate_attendance()
    {
        $data = [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'student_id' => $this->student->id,
            'date' => now()->toDateString(),
            'period_id' => $this->period->id,
            'attendances' => [
                ['student_id' => $this->student->id, 'status' => 'present'],
            ],
        ];

        $this->actingAs($this->teacherUser, 'sanctum')
             ->postJson('/api/attendances/mark', $data);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->postJson('/api/attendances/mark', $data);
        $response->assertStatus(422); // duplicate prevented
    }
}
