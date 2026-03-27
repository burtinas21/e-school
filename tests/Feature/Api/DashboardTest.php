<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teacherUser;
    protected User $studentUser;
    protected User $guardianUser;
    protected Teacher $teacher;
    protected Student $student;
    protected Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();


        $this->seed=false;

        // Create the four default roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Create one user for each role
        $this->adminUser = User::factory()->create(['role_id' => 1, 'email' => 'admin@test.com']);
        $this->teacherUser = User::factory()->create(['role_id' => 2, 'email' => 'teacher@test.com']);
        $this->studentUser = User::factory()->create(['role_id' => 3, 'email' => 'student@test.com']);
        $this->guardianUser = User::factory()->create(['role_id' => 4, 'email' => 'guardian@test.com']);

        // Create the corresponding profiles
        $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);
        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
        $this->guardian = Guardian::factory()->create(['user_id' => $this->guardianUser->id]);

        // Link the student to the guardian (optional, for parent dashboard tests)
        $this->student->guardian_id = $this->guardian->id;
        $this->student->save();
    }

    /* ==========================================================================
     * 1. AUTHENTICATION TESTS
     * ========================================================================== */

    /**
     * Test 1: Unauthenticated users cannot access the dashboard.
     *
     * This test ensures that the dashboard endpoint is protected and returns a 401
     * status when no valid authentication token is provided.
     */
    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    /* ==========================================================================
     * 2. ADMIN DASHBOARD TESTS
     * ========================================================================== */

    /**
     * Test 2: Admin can view the dashboard with all expected metrics.
     *
     * This test creates sample data (teachers, students, attendance) and verifies
     * that the admin dashboard contains the correct structure and calculated counts.
     */
    public function test_admin_can_view_dashboard_with_correct_structure(): void
    {
        // Create additional teachers and students
        Teacher::factory()->count(2)->create();
        Student::factory()->count(3)->create();

        // Create attendance records: one today, one yesterday
        $today = Carbon::today();
        Attendance::factory()->create(['date' => $today]);
        Attendance::factory()->create(['date' => $today->copy()->subDay()]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'total_teachers',
                         'total_students',
                         'total_users',
                         'total_sections',
                         'today_attendance',
                         'total_attendance',
                         'attendance_trend',
                         'recent_attendance',
                     ]
                 ]);

        $data = $response->json('data');
        // Total teachers: 1 (setUp) + 2 + 2 (from attendance factories) = 5
        $this->assertEquals(5, $data['total_teachers']);
        // Total students: 1 (setUp) + 3 + 2 (from attendance factories) = 6
        $this->assertEquals(6, $data['total_students']);
        // Total users: many due to factory creations
        $this->assertGreaterThanOrEqual(10, $data['total_users']);
        $this->assertEquals(1, $data['today_attendance']);
        $this->assertEquals(2, $data['total_attendance']);
        // Attendance trend should cover last 5 days
        $this->assertCount(5, $data['attendance_trend']);
        // Recent attendance should return up to 10 records, here we have 2
        $this->assertCount(2, $data['recent_attendance']);
    }

    /**
     * Test 3: Admin dashboard returns zero counts when there is no attendance data.
     *
     * This test ensures that the dashboard handles empty datasets gracefully and
     * still returns the correct structure with zero values.
     */
    public function test_admin_dashboard_shows_zero_trend_when_no_attendance(): void
    {
        // No attendance records created
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(0, $data['today_attendance']);
        $this->assertEquals(0, $data['total_attendance']);
        $this->assertCount(5, $data['attendance_trend']);
        foreach ($data['attendance_trend'] as $trend) {
            $this->assertEquals(0, $trend['count']);
        }
        $this->assertCount(0, $data['recent_attendance']);
    }

    /* ==========================================================================
     * 3. TEACHER DASHBOARD TESTS
     * ========================================================================== */

    /**
     * Test 4: Teacher can view dashboard with their assignments and today's attendance.
     *
     * This test sets up a section, subject, and a teacher assignment, then creates
     * students and attendance records. It verifies that the teacher dashboard
     * correctly shows the assignment and the attendance summary.
     */
    public function test_teacher_can_view_dashboard_with_assignments_and_attendance(): void
    {
        // Create a section and a subject that belongs to its grade
        $section = Section::factory()->create();
        $subject = Subject::factory()->create(['grade_id' => $section->grade_id]);

        // Assign the teacher to that section and subject
        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);

        // Create a student in that section
        $student = Student::factory()->create(['section_id' => $section->id]);

        // Mark attendance for today
        $today = Carbon::today();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'date'       => $today,
            'status'     => 'present',
        ]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'teacher',
                         'assignments',
                         'today_attendance',
                         'recent_attendance',
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['assignments']);
        $this->assertCount(1, $data['today_attendance']);
        $this->assertEquals(1, $data['today_attendance'][0]['total_students']);
        $this->assertEquals(1, $data['today_attendance'][0]['present']);
        $this->assertEquals(0, $data['today_attendance'][0]['absent']);
    }

    /**
     * Test 5: Teacher dashboard returns 403 if the teacher profile is missing.
     *
     * This test deletes the teacher profile and verifies that the dashboard responds
     * with a 403 status and an appropriate error message.
     */
    public function test_teacher_dashboard_returns_403_if_teacher_profile_missing(): void
    {
        $this->teacher->delete();

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Teacher profile not found']);
    }

    /**
     * Test 6: Teacher dashboard shows correct attendance numbers for their section.
     *
     * This test creates a section with 5 students, marks 3 present and 2 absent,
     * and checks that the dashboard summary reflects the correct counts.
     */
    public function test_teacher_dashboard_attendance_summary_shows_correct_numbers(): void
    {
        // Create section and subject
        $section = Section::factory()->create();
        $subject = Subject::factory()->create(['grade_id' => $section->grade_id]);

        // Assign teacher
        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);

        // Create 5 students in the section
        $students = Student::factory()->count(5)->create(['section_id' => $section->id]);

        // Today's attendance: 3 present, 2 absent
        $today = Carbon::today();
        for ($i = 0; $i < 3; $i++) {
            Attendance::factory()->create([
                'student_id' => $students[$i]->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $subject->id,
                'section_id' => $section->id,
                'date'       => $today,
                'status'     => 'present',
            ]);
        }

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data['today_attendance']);
        $summary = $data['today_attendance'][0];
        $this->assertEquals(5, $summary['total_students']);
        $this->assertEquals(3, $summary['present']);
        $this->assertEquals(2, $summary['absent']);
    }

    /* ==========================================================================
     * 4. STUDENT DASHBOARD TESTS
     * ========================================================================== */

    /**
     * Test 7: Student can view their dashboard with monthly attendance summary.
     *
     * This test creates attendance records for the current month and verifies that
     * the student dashboard shows the correct counts and the 7‑day trend.
     */
    public function test_student_can_view_dashboard_with_attendance_summary(): void
    {
        $monthStart = Carbon::now()->startOfMonth();

        // Create two attendance records: present on the first day, absent on the second
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'date'       => $monthStart,
            'status'     => 'present',
        ]);
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'date'       => $monthStart->copy()->addDay(),
            'status'     => 'absent',
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'student',
                         'attendance_summary',
                         'attendance_trend',
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['attendance_summary']['present']);
        $this->assertEquals(1, $data['attendance_summary']['absent']);
        $this->assertCount(7, $data['attendance_trend']); // last 7 days
    }

    /**
     * Test 8: Student dashboard returns 403 if the student profile is missing.
     */
    public function test_student_dashboard_returns_403_if_student_profile_missing(): void
    {
        $this->student->delete();

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Student profile not found']);
    }

    /**
     * Test 9: Student dashboard trend shows null for days without attendance.
     *
     * This test verifies that when there are no attendance records for a student,
     * the 7‑day trend array contains null statuses for each day.
     */
    public function test_student_dashboard_attendance_trend_contains_null_for_days_without_record(): void
    {
        // No attendance records at all
        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(7, $data['attendance_trend']);
        foreach ($data['attendance_trend'] as $trend) {
            $this->assertNull($trend['status']);
        }
    }

    /* ==========================================================================
     * 5. GUARDIAN (PARENT) DASHBOARD TESTS
     * ========================================================================== */

    /**
     * Test 10: Guardian can view dashboard with their children and attendance.
     *
     * This test creates attendance records for the child (student) and verifies that
     * the guardian dashboard correctly lists the child and the attendance summary.
     */
    public function test_guardian_can_view_dashboard_with_children_and_attendance(): void
    {
        $monthStart = Carbon::now()->startOfMonth();

        // Create attendance for the child (linked in setUp)
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'date'       => $monthStart,
            'status'     => 'present',
        ]);
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'date'       => $monthStart->copy()->addDay(),
            'status'     => 'absent',
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'guardian',
                         'children' => [
                             '*' => ['id', 'name', 'admission_no', 'grade', 'section', 'attendance']
                         ]
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['children']);
        $child = $data['children'][0];
        $this->assertEquals($this->student->id, $child['id']);
        $this->assertEquals(1, $child['attendance']['present']);
        $this->assertEquals(1, $child['attendance']['absent']);
    }

    /**
     * Test 11: Guardian dashboard returns 403 if the guardian profile is missing.
     */
    public function test_guardian_dashboard_returns_403_if_guardian_profile_missing(): void
    {
        $this->guardian->delete();

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/dashboard');

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Guardian profile not found']);
    }
}
