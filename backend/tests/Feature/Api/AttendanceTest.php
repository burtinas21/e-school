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
use App\Models\Setting;
use App\Models\CalendarEvent;
use App\Models\Guardian;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $teacherUser;
    protected $teacher;
    protected $student;
    protected $subject;
    protected $section;
    protected $period;
    protected $grade;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-30')); // Monday

        // Roles
        foreach(['Admin', 'Teacher', 'Student', 'Guardian'] as $name) {
            \App\Models\Role::create(['name' => $name]);
        }

        // Settings
        Setting::set('allow_weekend_marking', false);

        // Seed data
        $this->adminUser = User::factory()->create(['role_id' => 1]);
        $this->teacherUser = User::factory()->create(['role_id' => 2]);
        $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);
        $this->grade = Grade::factory()->create();
        $this->section = Section::factory()->create(['grade_id' => $this->grade->id]);
        $this->subject = Subject::factory()->create(['grade_id' => $this->grade->id]);
        $this->period = Period::factory()->create();

        TeacherAssignment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
        ]);

        $guardian = Guardian::factory()->create();
        $this->student = Student::factory()->create([
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'guardian_id' => $guardian->id,
        ]);
    }

    /**
     * Test Success.
     */
    public function test_basic_marking_works()
    {
        $response = $this->actingAs($this->teacherUser, 'sanctum')->postJson('/api/attendances/mark', [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'period_id' => $this->period->id,
            'date' => '2026-03-30',
            'attendances' => [['student_id' => $this->student->id, 'status' => 'present']],
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test Weekend Block.
     */
    public function test_weekend_block_works()
    {
        $response = $this->actingAs($this->teacherUser, 'sanctum')->postJson('/api/attendances/mark', [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'period_id' => $this->period->id,
            'date' => '2026-03-29', // Sunday
            'attendances' => [['student_id' => $this->student->id, 'status' => 'present']],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test Holiday Block.
     */
    public function test_holiday_block_works()
    {
        CalendarEvent::create([
            'title' => 'Holiday',
            'event_type' => 'holiday',
            'start_date' => '2026-03-30',
            'end_date' => '2026-03-30',
            'affects_attendance' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->teacherUser, 'sanctum')->postJson('/api/attendances/mark', [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'period_id' => $this->period->id,
            'date' => '2026-03-30',
            'attendances' => [['student_id' => $this->student->id, 'status' => 'present']],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test Notification Trigger.
     */
    public function test_notification_is_created()
    {
        $this->actingAs($this->teacherUser, 'sanctum')->postJson('/api/attendances/mark', [
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'period_id' => $this->period->id,
            'date' => '2026-03-30',
            'attendances' => [['student_id' => $this->student->id, 'status' => 'absent']],
        ]);

        $this->assertDatabaseHas('notifications', [
            'student_id' => $this->student->id,
            'type' => 'absence',
        ]);
    }
}
