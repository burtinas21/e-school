<?php

namespace Tests\Feature\Api;

use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Period;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
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
     * Test 1: Unauthenticated user cannot access read endpoints.
     */
    public function test_unauthenticated_cannot_access_read_endpoints(): void
    {
        $section = Section::factory()->create();
        $teacher = Teacher::factory()->create();

        $this->getJson('/api/schedules/section/' . $section->id)->assertStatus(401);
        $this->getJson('/api/schedules/teacher/' . $teacher->id)->assertStatus(401);
        $this->getJson('/api/schedules/weekly/' . $section->id)->assertStatus(401);
        $this->getJson('/api/schedules/active/' . $section->id)->assertStatus(401);
    }

   /**
 * Test 2: Authenticated user can get section schedule.
 */
public function test_authenticated_user_can_get_section_schedule(): void
{
    $section = Section::factory()->create();
    $schedule = Schedule::factory()->forSection($section)->create();

    $response = $this->actingAs($this->regularUser, 'sanctum')
                     ->getJson('/api/schedules/section/' . $section->id);

    $response->assertStatus(200)
             ->assertJson(['success' => true]);

    // Verify that the response contains the expected day key
    $data = $response->json('data');
    $this->assertArrayHasKey($schedule->day_of_week, $data);

    $daySchedules = $data[$schedule->day_of_week];
    $this->assertCount(1, $daySchedules);

    $first = $daySchedules[0];
    $this->assertArrayHasKey('id', $first);
    $this->assertArrayHasKey('subject', $first);
    $this->assertArrayHasKey('teacher', $first);
    $this->assertArrayHasKey('period', $first);
    //$this->assertArrayHasKey('time_range', $first['period']); // nested

    // Verify period relationship (should contain at least id and name)
    $this->assertIsArray($first['period']);
    $this->assertArrayHasKey('id', $first['period']);
    $this->assertArrayHasKey('name', $first['period']);

    // Verify subject and teacher data (optional, but good)
    $this->assertIsArray($first['subject']);
    $this->assertArrayHasKey('id', $first['subject']);
    $this->assertArrayHasKey('name', $first['subject']);

    $this->assertIsArray($first['teacher']);
    $this->assertArrayHasKey('id', $first['teacher']);
    $this->assertArrayHasKey('user', $first['teacher']);


}
    /**
     * Test 3: Authenticated user can get teacher schedule.
     */
    public function test_authenticated_user_can_get_teacher_schedule(): void
    {
        $teacher = Teacher::factory()->create();
        $schedule = Schedule::factory()->forTeacher($teacher)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/schedules/teacher/' . $teacher->id);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         $schedule->day_of_week => [['id', 'subject', 'section', 'period']]
                     ]
                 ]);
    }

    /**
     * Test 4: Authenticated user can get weekly schedule grid.
     */
    public function test_authenticated_user_can_get_weekly_schedule_grid(): void
    {
        $section = Section::factory()->create();
        $period = Period::factory()->create(['period_number' => 1]);
        $schedule = Schedule::factory()->forSection($section)->atPeriod($period)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/schedules/weekly/' . $section->id);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'periods' => [['id', 'name', 'number', 'time_range']],
                         'grid' => [
                             ['day', 'period_1']
                         ]
                     ]
                 ]);

        // Verify the schedule appears in the correct day/period cell
        $grid = $response->json('data.grid');
        $dayRow = collect($grid)->firstWhere('day', $schedule->day_of_week);
        $this->assertNotNull($dayRow);
        $this->assertArrayHasKey('period_1', $dayRow);
        $this->assertEquals($schedule->subject->name, $dayRow['period_1']['subject']);
    }

    /**
     * Test 5: Authenticated user can get active schedules (dropdown).
     */
    public function test_authenticated_user_can_get_active_schedules(): void
    {
        $section = Section::factory()->create();
        $active = Schedule::factory()->forSection($section)->create();
        $inactive = Schedule::factory()->forSection($section)->inactive()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/schedules/active/' . $section->id);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(1, 'data'); // only active schedule

        $this->assertEquals($active->id, $response->json('data.0.id'));
    }

    /* ==========================================================================
     * WRITE ENDPOINTS (admin only)
     * ========================================================================== */

    /**
     * Test 6: Admin can create a schedule (with all validations).
     */
    public function test_admin_can_create_schedule(): void
    {
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        $data = [
            'grade_id'    => $grade->id,
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'teacher_id'  => $teacher->id,
            'period_id'   => $period->id,
            'day_of_week' => 'Monday',
            'is_active'   => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/schedules/', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Schedule created successfully',
                     'data' => [
                         'grade_id'    => $grade->id,
                         'section_id'  => $section->id,
                         'subject_id'  => $subject->id,
                         'teacher_id'  => $teacher->id,
                         'period_id'   => $period->id,
                         'day_of_week' => 'Monday',
                         'is_active'   => true,
                     ]
                 ]);

        $this->assertDatabaseHas('schedules', [
            'grade_id'   => $grade->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'period_id'  => $period->id,
            'day_of_week'=> 'Monday',
            'is_active'  => true,
        ]);
    }

    /**
     * Test 7: Non-admin cannot create schedule.
     */
    public function test_non_admin_cannot_create_schedule(): void
    {
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        $data = [
            'grade_id'    => $grade->id,
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'teacher_id'  => $teacher->id,
            'period_id'   => $period->id,
            'day_of_week' => 'Monday',
        ];

        $this->actingAs($this->regularUser, 'sanctum')
             ->postJson('/api/schedules/', $data)
             ->assertStatus(403);

        $this->assertDatabaseMissing('schedules', $data);
    }

    /**
     * Test 8: Admin can update schedule.
     */
    public function test_admin_can_update_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        $newSubject = Subject::factory()->create(['grade_id' => $schedule->grade_id]);

        $updateData = ['subject_id' => $newSubject->id, 'is_active' => false];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/schedules/' . $schedule->id, $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Schedule updated successfully',
                     'data' => [
                         'id'         => $schedule->id,
                         'subject_id' => $newSubject->id,
                         'is_active'  => false,
                     ]
                 ]);

        $this->assertDatabaseHas('schedules', [
            'id'         => $schedule->id,
            'subject_id' => $newSubject->id,
            'is_active'  => false,
        ]);

        // Logic Upgrade Verification: Auto-Notify Teacher
        $this->assertDatabaseHas('notifications', [
            'teacher_id' => $schedule->teacher_id,
            'type'       => 'event',
        ]);
    }

    /**
     * Test 9: Non-admin cannot update schedule.
     */
    public function test_non_admin_cannot_update_schedule(): void
    {
        $schedule = Schedule::factory()->create();

        $this->actingAs($this->regularUser, 'sanctum')
             ->putJson('/api/schedules/' . $schedule->id, ['is_active' => false])
             ->assertStatus(403);
    }

    /**
     * Test 10: Admin can delete schedule.
     */
    public function test_admin_can_delete_schedule(): void
    {
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/schedules/' . $schedule->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Schedule deleted successfully',
                 ]);

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    /**
     * Test 11: Non-admin cannot delete schedule.
     */
    public function test_non_admin_cannot_delete_schedule(): void
    {
        $schedule = Schedule::factory()->create();

        $this->actingAs($this->regularUser, 'sanctum')
             ->deleteJson('/api/schedules/' . $schedule->id)
             ->assertStatus(403);
    }

    /**
     * Test 12: Cannot create duplicate schedule (same section, day, period).
     */
    public function test_cannot_create_duplicate_schedule(): void
    {
        $existing = Schedule::factory()->create();

        $data = [
            'grade_id'    => $existing->grade_id,
            'section_id'  => $existing->section_id,
            'subject_id'  => $existing->subject_id,
            'teacher_id'  => $existing->teacher_id,
            'period_id'   => $existing->period_id,
            'day_of_week' => $existing->day_of_week,
        ];

        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/schedules/', $data)
             ->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => "A schedule already exists for this section on {$existing->day_of_week} during this period",
             ]);
    }

    /**
 * Test 13: Cannot schedule teacher in two sections same day/period.
 */
public function test_cannot_schedule_teacher_in_conflicting_sections(): void
{
    $teacher = Teacher::factory()->create();
    $grade = Grade::factory()->create();
    $section1 = Section::factory()->create([
        'grade_id' => $grade->id,
        'name'     => 'Section A'  // unique name
    ]);
    $section2 = Section::factory()->create([
        'grade_id' => $grade->id,
        'name'     => 'Section B'  // unique name
    ]);
    $subject1 = Subject::factory()->create(['grade_id' => $grade->id]);
    $subject2 = Subject::factory()->create(['grade_id' => $grade->id]);
    $period = Period::factory()->create();
    $day = 'Monday';

    // Create first schedule for teacher
    Schedule::factory()->create([
        'teacher_id'  => $teacher->id,
        'section_id'  => $section1->id,
        'subject_id'  => $subject1->id,
        'period_id'   => $period->id,
        'day_of_week' => $day,
        'grade_id'    => $grade->id,
    ]);

    // Attempt to create second schedule for same teacher on same day/period
    $data = [
        'grade_id'    => $grade->id,
        'section_id'  => $section2->id,
        'subject_id'  => $subject2->id,
        'teacher_id'  => $teacher->id,
        'period_id'   => $period->id,
        'day_of_week' => $day,
    ];

    $this->actingAs($this->adminUser, 'sanctum')
         ->postJson('/api/schedules/', $data)
         ->assertStatus(422)
         ->assertJson([
             'success' => false,
             'message' => 'This teacher is already scheduled on the same day and period in another section',
         ]);
}

    /**
     * Test 14: Cannot create schedule if subject doesn't belong to section's grade.
     */
    public function test_cannot_create_schedule_with_wrong_subject_grade(): void
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade1->id]);
        $subject = Subject::factory()->create(['grade_id' => $grade2->id]); // different grade
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        $data = [
            'grade_id'    => $grade1->id,
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'teacher_id'  => $teacher->id,
            'period_id'   => $period->id,
            'day_of_week' => 'Monday',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/schedules/', $data)
             ->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'Subject does not belong to the grade of the selected section',
             ]);
    }

    /**
     * Test 15: Cannot create schedule if grade_id doesn't match section's grade.
     */
    public function test_cannot_create_schedule_with_mismatched_grade(): void
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade1->id]);
        $subject = Subject::factory()->create(['grade_id' => $grade1->id]);
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        $data = [
            'grade_id'    => $grade2->id, // mismatched
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'teacher_id'  => $teacher->id,
            'period_id'   => $period->id,
            'day_of_week' => 'Monday',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/schedules/', $data)
             ->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'Grade ID does not match the section\'s grade',
             ]);
    }

    /**
     * Test 16: Update non-existent schedule returns 404.
     */
    public function test_update_non_existent_schedule_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->putJson('/api/schedules/99999', ['is_active' => false])
             ->assertStatus(404)
             ->assertJson([
                 'success' => false,
                 'message' => 'Schedule not found',
             ]);
    }

    /**
     * Test 17: Delete non-existent schedule returns 404.
     */
    public function test_delete_non_existent_schedule_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->deleteJson('/api/schedules/99999')
             ->assertStatus(404)
             ->assertJson([
                 'success' => false,
                 'message' => 'Schedule not found',
             ]);
    }
}
