<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teacherUser;
    protected User $studentUser;
    protected Teacher $teacher;
    protected Student $student;
    protected Section $section;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed=false;

        // ==========================================================================
        // 1. Create required roles
        // ==========================================================================
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // ==========================================================================
        // 2. Create users with different roles
        // ==========================================================================
        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'email'   => 'admin@test.com'
        ]);
        $this->teacherUser = User::factory()->create([
            'role_id' => 2,
            'email'   => 'teacher@test.com'
        ]);
        $this->studentUser = User::factory()->create([
            'role_id' => 3,
            'email'   => 'student@test.com'
        ]);

        // ==========================================================================
        // 3. Create related profiles
        // ==========================================================================
        $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);
        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);

        // ==========================================================================
        // 4. Create a section and subject that belong to the same grade
        // ==========================================================================
        $this->section = Section::factory()->create();
        $this->subject = Subject::factory()->create(['grade_id' => $this->section->grade_id]);

        // ==========================================================================
        // 5. Assign the teacher to this section and subject
        // ==========================================================================
        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    /* ==========================================================================
     * SECTION 1: AUTHENTICATION & AUTHORIZATION
     * ========================================================================== */

    /**
     * Test 1: Unauthenticated users cannot access the report endpoint.
     *
     * This ensures that the report endpoint is properly protected by authentication.
     */
    public function test_unauthenticated_cannot_access_report(): void
    {
        $this->getJson('/api/reports/attendance')->assertStatus(401);
    }

    /**
     * Test 2: Students (role 3) cannot access the report.
     *
     * Only admins and teachers are allowed to generate reports.
     */
    public function test_student_cannot_access_report(): void
    {
        $this->actingAs($this->studentUser, 'sanctum')
             ->getJson('/api/reports/attendance')
             ->assertStatus(403);
    }

    /**
     * Test 3: Teachers can access the report.
     *
     * Teachers are allowed to generate reports for their assigned sections.
     */
    public function test_teacher_can_access_report(): void
    {
        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=2026-01-01&end_date=2026-12-31');
        $response->assertStatus(200);
    }

    /**
     * Test 4: Teachers see only attendance records from their assigned sections.
     *
     * This test verifies that the role‑based restriction works: teachers cannot view
     * attendance from sections they are not assigned to.
     */
    public function test_teacher_sees_only_assigned_sections(): void
    {
        // Create attendance in the teacher's assigned section
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'date'       => Carbon::today(),
        ]);

        // Create attendance in a different section (not assigned)
        $otherSection = Section::factory()->create();
        $otherStudent = Student::factory()->create(['section_id' => $otherSection->id]);
        Attendance::factory()->create([
            'student_id' => $otherStudent->id,
            'section_id' => $otherSection->id,
            'date'       => Carbon::today(),
        ]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=' . Carbon::today()->toDateString() . '&end_date=' . Carbon::today()->toDateString());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.records'));
    }

    /* ==========================================================================
     * SECTION 2: DATE RANGE FILTERING
     * ========================================================================== */

    /**
     * Test 5: Custom date range (start_date and end_date) works.
     *
     * Admin can specify any date interval; only records within that range are returned.
     */
    public function test_can_filter_by_custom_date_range(): void
    {
        $date1 = Carbon::today()->subDays(2);
        $date2 = Carbon::today()->subDays(1);
        $outside = Carbon::today();

        Attendance::factory()->create(['date' => $date1]);
        Attendance::factory()->create(['date' => $date2]);
        Attendance::factory()->create(['date' => $outside]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=' . $date1->toDateString() . '&end_date=' . $date2->toDateString());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.records'));
    }

    /**
     * Test 6: Predefined range 'this_week' returns correct records.
     *
     * This uses the built‑in range shortcut, reducing client‑side date calculation.
     */
    public function test_can_filter_by_range_this_week(): void
    {
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();

        Attendance::factory()->create(['date' => $thisWeek->addDay()]);
        Attendance::factory()->create(['date' => $lastWeek]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?range=this_week');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.records'));
    }

    /**
     * Test 7: Predefined range 'this_semester' returns correct records.
     *
     * The controller dynamically calculates semester boundaries. This test ensures
     * that records inside the semester are included and those outside are excluded.
     */
    public function test_can_filter_by_range_this_semester(): void
    {
        $semester = $this->getCurrentSemester();

        Attendance::factory()->create(['date' => $semester['start']->addDay()]); // inside
        Attendance::factory()->create(['date' => $semester['end']->subDay()]);   // inside
        Attendance::factory()->create(['date' => $semester['start']->subMonth()]); // outside

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?range=this_semester');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.records'));
    }

    // Helper to replicate controller's semester logic for testing
    private function getCurrentSemester(): array
    {
        $now = Carbon::now();
        $year = $now->year;
        if ($now->month >= 9) {
            $start = Carbon::create($year, 9, 1);
            $end = Carbon::create($year + 1, 1, 31);
        } elseif ($now->month <= 1) {
            $start = Carbon::create($year - 1, 9, 1);
            $end = Carbon::create($year, 1, 31);
        } else {
            $start = Carbon::create($year, 2, 1);
            $end = Carbon::create($year, 8, 31);
        }
        return ['start' => $start, 'end' => $end];
    }

    /* ==========================================================================
     * SECTION 3: STATUS FILTERING
     * ========================================================================== */
/**
 * Test 8: Filter by attendance status (present, absent, late, permission).
 *
 * Allows users to see only records with a particular status.
 */
    public function test_can_filter_by_status(): void
       {
            $date = '2026-03-25'; // fixed date within our range
            Attendance::factory()->create(['date' => $date, 'status' => 'present']);
            Attendance::factory()->create(['date' => $date, 'status' => 'absent']);
            Attendance::factory()->create(['date' => $date, 'status' => 'late']);

    $response = $this->actingAs($this->adminUser, 'sanctum')
                     ->getJson('/api/reports/attendance?start_date=2026-01-01&end_date=2026-12-31&status=present');

        $response->assertStatus(200);
        $records = $response->json('data.records');
        $this->assertCount(1, $records);
        $this->assertEquals('present', $records[0]['status']);

    }

    /* ==========================================================================
     * SECTION 4: PAGINATION
     * ========================================================================== */

    /**
     * Test 9: Pagination works as expected.
     *
     * The report endpoint should support pagination to avoid overwhelming the client.
     */
    public function test_pagination_works(): void
    {
        Attendance::factory()->count(30)->create(['date' => Carbon::today()]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=' . Carbon::today()->toDateString() . '&end_date=' . Carbon::today()->toDateString() . '&per_page=10');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data['records']);
        $this->assertEquals(30, $data['pagination']['total']);
        $this->assertEquals(10, $data['pagination']['per_page']);
    }

    /* ==========================================================================
     * SECTION 5: GROUPING
     * ========================================================================== */

    /**
     * Test 10: Group by day.
     *
     * Groups attendance records by individual calendar day, summing status counts.
     */
    public function test_can_group_by_day(): void
    {
        $day1 = Carbon::today();
        $day2 = Carbon::today()->addDay();

        Attendance::factory()->create(['date' => $day1, 'status' => 'present']);
        Attendance::factory()->create(['date' => $day1, 'status' => 'absent']);
        Attendance::factory()->create(['date' => $day2, 'status' => 'late']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=' . $day1->toDateString() . '&end_date=' . $day2->toDateString() . '&group_by=day');

        $response->assertStatus(200);
        $grouped = $response->json('data.grouped');
        $this->assertCount(2, $grouped);
        $this->assertEquals($day1->toDateString(), $grouped[0]['date']);
        $this->assertEquals(1, $grouped[0]['present']);
        $this->assertEquals(1, $grouped[0]['absent']);
        $this->assertEquals(1, $grouped[1]['late']);
    }

    /**
     * Test 11: Group by week.
     *
     * Groups records by ISO week number, summing counts for that week.
     */
    public function test_can_group_by_week(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        Attendance::factory()->create(['date' => $weekStart, 'status' => 'present']);
        Attendance::factory()->create(['date' => $weekStart->copy()->addDay(), 'status' => 'present']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=' . $weekStart->toDateString() . '&end_date=' . $weekStart->copy()->endOfWeek()->toDateString() . '&group_by=week');

        $response->assertStatus(200);
        $grouped = $response->json('data.grouped');
        $this->assertCount(1, $grouped);
        $this->assertEquals($weekStart->format('Y-\WW'), $grouped[0]['date']);
        $this->assertEquals(2, $grouped[0]['present']);
    }

    /**
     * Test 12: Group by month.
     *
     * Groups by year and month (e.g., 2026-03).
     */
    public function test_can_group_by_month(): void
    {
        $month = Carbon::create(2026, 3, 1);
        Attendance::factory()->create(['date' => $month->copy()->addDay(), 'status' => 'present']);
        Attendance::factory()->create(['date' => $month->copy()->addDays(5), 'status' => 'present']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=2026-03-01&end_date=2026-03-31&group_by=month');

        $response->assertStatus(200);
        $grouped = $response->json('data.grouped');
        $this->assertCount(1, $grouped);
        $this->assertEquals('2026-03', $grouped[0]['date']);
        $this->assertEquals(2, $grouped[0]['present']);
    }

    /* ==========================================================================
     * SECTION 6: CSV EXPORT
     * ========================================================================== */

    /**
     * Test 13: CSV export returns a downloadable file with correct headers.
     *
     * The format=csv parameter should trigger a CSV file download.
     */
    public function test_can_export_csv(): void
    {
        Attendance::factory()->count(3)->create(['date' => '2026-06-01']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/reports/attendance?start_date=2026-01-01&end_date=2026-12-31&format=csv');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="attendance_report_', $response->headers->get('Content-Disposition'));
    }

    /* ==========================================================================
     * SECTION 7: VALIDATION
     * ========================================================================== */

    /**
     * Test 14: Missing start_date and end_date when range not provided → 422.
     */
    public function test_validation_requires_start_and_end_date_when_range_not_provided(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/reports/attendance')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /**
     * Test 15: Invalid range value → 422.
     */
    public function test_validation_rejects_invalid_range(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/reports/attendance?range=invalid')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['range']);
    }

    /**
     * Test 16: Invalid status value → 422.
     */
    public function test_validation_rejects_invalid_status(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/reports/attendance?start_date=2026-01-01&end_date=2026-12-31&status=invalid')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test 17: Invalid group_by value → 422.
     */
    public function test_validation_rejects_invalid_group_by(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/reports/attendance?start_date=2026-01-01&end_date=2026-12-31&group_by=invalid')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['group_by']);
    }

    /**
     * Test 18: End date before start date → 422.
     */
    public function test_validation_rejects_end_date_before_start_date(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/reports/attendance?start_date=2026-12-31&end_date=2026-01-01')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['end_date']);
    }
}
