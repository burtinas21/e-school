<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

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
     * Test 1: Unauthenticated user cannot access any read endpoints.
     */
    public function test_unauthenticated_cannot_access_read_endpoints(): void
    {
        $this->getJson('/api/calendar-events')->assertStatus(401);
        $this->getJson('/api/calendar-events/1')->assertStatus(401);
        $this->getJson('/api/calendar-events/check-date/2026-03-25')->assertStatus(401);
        $this->getJson('/api/calendar-events/upcoming')->assertStatus(401);
        $this->getJson('/api/calendar-events/month/2026/03')->assertStatus(401);
    }

    /**
     * Test 2: Authenticated user can list events.
     */
    public function test_authenticated_user_can_list_events(): void
    {
        CalendarEvent::factory()->count(3)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/calendar-events');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'title', 'event_type', 'start_date', 'end_date']
                     ]
                 ]);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test 3: Authenticated user can view a single event.
     */
    public function test_authenticated_user_can_view_single_event(): void
    {
        $event = CalendarEvent::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/calendar-events/' . $event->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $event->id,
                         'title' => $event->title,
                         'event_type' => $event->event_type,
                     ]
                 ]);
    }

    /**
     * Test 4: Authenticated user can check if a date is a holiday (special endpoint).
     */
    public function test_authenticated_user_can_check_date_holiday(): void
    {
        $date = '2026-12-25';
        $event = CalendarEvent::factory()->create([
            'event_type' => 'holiday',
            'start_date' => $date,
            'end_date'   => $date,
            'affects_attendance' => true,
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/calendar-events/check-date/' . $date);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'date' => $date,
                         'is_holiday' => true,
                         'event' => [
                             'id' => $event->id,
                             'title' => $event->title,
                         ]
                     ]
                 ]);
    }

    /**
     * Test 5: Authenticated user can get upcoming events.
     */
    public function test_authenticated_user_can_get_upcoming_events(): void
    {
        // Create event in the past, one in the future, and one far future
        $past = CalendarEvent::factory()->create([
            'start_date' => Carbon::now()->subDays(5),
            'end_date'   => Carbon::now()->subDays(5),
        ]);
        $upcoming = CalendarEvent::factory()->create([
            'start_date' => Carbon::now()->addDays(2),
            'end_date'   => Carbon::now()->addDays(2),
        ]);
        $far = CalendarEvent::factory()->create([
            'start_date' => Carbon::now()->addDays(40),
            'end_date'   => Carbon::now()->addDays(40),
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/calendar-events/upcoming?days=30');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($upcoming->id, $data[0]['id']);
    }

    /**
     * Test 6: Authenticated user can get events for a specific month.
     */
    public function test_authenticated_user_can_get_month_events(): void
    {
        $year = 2026;
        $month = 3;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $event1 = CalendarEvent::factory()->create([
            'start_date' => $start,
            'end_date'   => $start,
        ]);
        $event2 = CalendarEvent::factory()->create([
            'start_date' => $end,
            'end_date'   => $end,
        ]);
        $outside = CalendarEvent::factory()->create([
            'start_date' => $start->copy()->subMonth(),
            'end_date'   => $start->copy()->subMonth(),
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson("/api/calendar-events/month/{$year}/{$month}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertContains($event1->id, array_column($data, 'id'));
        $this->assertContains($event2->id, array_column($data, 'id'));
    }

    /* ==========================================================================
     * WRITE ENDPOINTS (admin only)
     * ========================================================================== */

    /**
     * Test 7: Unauthenticated cannot create event.
     */
    public function test_unauthenticated_cannot_create_event(): void
    {
        $data = [
            'title' => 'Test Event',
            'event_type' => 'event',
            'start_date' => '2026-03-25',
            'end_date' => '2026-03-25',
        ];
        $this->postJson('/api/calendar-events', $data)->assertStatus(401);
    }

    /**
     * Test 8: Non-admin authenticated user cannot create event.
     */
    public function test_non_admin_cannot_create_event(): void
    {
        $data = [
            'title' => 'Test Event',
            'event_type' => 'event',
            'start_date' => '2026-03-25',
            'end_date' => '2026-03-25',
        ];
        $this->actingAs($this->regularUser, 'sanctum')
             ->postJson('/api/calendar-events', $data)
             ->assertStatus(403);
    }

    /**
     * Test 9: Admin can create event.
     */
    public function test_admin_can_create_event(): void
    {
        $data = [
            'title' => 'School Holiday',
            'description' => 'No classes',
            'event_type' => 'holiday',
            'start_date' => '2026-12-25',
            'end_date' => '2026-12-25',
            'affects_attendance' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/calendar-events', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calendar event created successfully',
                     'data' => [
                         'title' => 'School Holiday',
                         'event_type' => 'holiday',
                         'start_date' => '2026-12-25',
                         'end_date' => '2026-12-25',
                         'affects_attendance' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'School Holiday',
            'event_type' => 'holiday',
        ]);
    }

    /**
     * Test 10: Admin can update an event.
     */
    public function test_admin_can_update_event(): void
    {
        $event = CalendarEvent::factory()->create(['title' => 'Old Title']);
        $updateData = ['title' => 'New Title'];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/calendar-events/' . $event->id, $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calendar event updated successfully',
                     'data' => ['title' => 'New Title']
                 ]);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'New Title'
        ]);
    }

    /**
     * Test 11: Non-admin cannot update event.
     */
    public function test_non_admin_cannot_update_event(): void
    {
        $event = CalendarEvent::factory()->create();
        $this->actingAs($this->regularUser, 'sanctum')
             ->putJson('/api/calendar-events/' . $event->id, ['title' => 'New'])
             ->assertStatus(403);
    }

    /**
     * Test 12: Admin can delete event.
     */
    public function test_admin_can_delete_event(): void
    {
        $event = CalendarEvent::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson('/api/calendar-events/' . $event->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Calendar event deleted successfully'
                 ]);

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    /**
     * Test 13: Non-admin cannot delete event.
     */
    public function test_non_admin_cannot_delete_event(): void
    {
        $event = CalendarEvent::factory()->create();
        $this->actingAs($this->regularUser, 'sanctum')
             ->deleteJson('/api/calendar-events/' . $event->id)
             ->assertStatus(403);
    }

    /* ==========================================================================
     * VALIDATION TESTS
     * ========================================================================== */

    /**
     * Test 14: Create event fails if required fields missing.
     */
    public function test_create_event_validation_required_fields(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/calendar-events', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title', 'event_type', 'start_date', 'end_date']);
    }

    /**
     * Test 15: Create event fails if end_date is before start_date.
     */
    public function test_create_event_validation_end_date_before_start(): void
    {
        $data = [
            'title' => 'Invalid',
            'event_type' => 'event',
            'start_date' => '2026-12-25',
            'end_date' => '2026-12-24',
        ];
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/calendar-events', $data)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Test 16: Create event fails if event_type not in allowed list.
     */
    public function test_create_event_validation_invalid_event_type(): void
    {
        $data = [
            'title' => 'Invalid',
            'event_type' => 'invalid_type',
            'start_date' => '2026-12-25',
            'end_date' => '2026-12-25',
        ];
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/calendar-events', $data)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['event_type']);
    }

    /**
     * Test 17: Create event with recurring pattern but no pattern? Actually pattern only allowed if is_recurring true? Our validation doesn't enforce that yet. We'll just test that invalid pattern is rejected.
     */
    public function test_create_event_validation_invalid_recurring_pattern(): void
    {
        $data = [
            'title' => 'Recurring Event',
            'event_type' => 'event',
            'start_date' => '2026-12-25',
            'end_date' => '2026-12-25',
            'is_recurring' => true,
            'recurring_pattern' => 'invalid_pattern',
        ];
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/calendar-events', $data)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['recurring_pattern']);
    }

    /* ==========================================================================
     * ATTENDANCE BLOCKING LOGIC TESTS (via static methods)
     * ========================================================================== */

    /**
     * Test 18: Attendance cannot be marked on weekends.
     */
    public function test_attendance_blocked_on_weekend(): void
    {
        // Pick a Saturday
        $date = Carbon::parse('2026-03-28'); // Saturday
        $this->assertTrue(CalendarEvent::isWeekend($date));
        $this->assertFalse(CalendarEvent::canMarkAttendance($date));
        $reason = CalendarEvent::getAttendanceBlockReason($date);
        $this->assertEquals('weekend', $reason['reason']);
    }

    /**
     * Test 19: Attendance can be marked on weekdays if no holiday.
     */
    public function test_attendance_allowed_on_weekday_without_holiday(): void
    {
        $date = Carbon::parse('2026-03-25'); // Wednesday
        $this->assertFalse(CalendarEvent::isWeekend($date));
        $this->assertTrue(CalendarEvent::canMarkAttendance($date));
        $this->assertNull(CalendarEvent::getAttendanceBlockReason($date));
    }

    /**
     * Test 20: Attendance blocked on holiday that affects all.
     */
    public function test_attendance_blocked_on_holiday(): void
    {
        $date = Carbon::parse('2026-03-25');
        CalendarEvent::factory()->create([
            'event_type' => 'holiday',
            'start_date' => $date,
            'end_date' => $date,
            'affects_attendance' => true,
            'applicable_grades' => null,
            'applicable_sections' => null,
        ]);

        $this->assertFalse(CalendarEvent::canMarkAttendance($date));
        $reason = CalendarEvent::getAttendanceBlockReason($date);
        $this->assertEquals('holiday', $reason['reason']);
        $this->assertEquals('School is closed: ' . CalendarEvent::first()->title, $reason['message']);
    }

    /**
     * Test 21: Attendance blocked on holiday that applies only to specific grade.
     */
    public function test_attendance_blocked_on_holiday_for_specific_grade(): void
    {
        $grade1 = Grade::factory()->create();
        $grade2 = Grade::factory()->create();
        $date = Carbon::parse('2026-03-25');

        // Holiday only for grade1
        CalendarEvent::factory()->create([
            'event_type' => 'holiday',
            'start_date' => $date,
            'end_date' => $date,
            'affects_attendance' => true,
            'applicable_grades' => $grade1->id,
            'applicable_sections' => null,
        ]);

        $student1 = Student::factory()->create(['grade_id' => $grade1->id]);
        $student2 = Student::factory()->create(['grade_id' => $grade2->id]);

        $this->assertFalse(CalendarEvent::canMarkAttendance($date, $student1->id));
        $this->assertTrue(CalendarEvent::canMarkAttendance($date, $student2->id));
    }

    /**
     * Test 22: Attendance blocked on holiday that applies only to specific section.
     */
    public function test_attendance_blocked_on_holiday_for_specific_section(): void
    {
        $section1 = Section::factory()->create();
        $section2 = Section::factory()->create();
        $date = Carbon::parse('2026-03-25');

        CalendarEvent::factory()->create([
            'event_type' => 'holiday',
            'start_date' => $date,
            'end_date' => $date,
            'affects_attendance' => true,
            'applicable_grades' => null,
            'applicable_sections' => $section1->id,
        ]);

        $student1 = Student::factory()->create(['section_id' => $section1->id]);
        $student2 = Student::factory()->create(['section_id' => $section2->id]);

        $this->assertFalse(CalendarEvent::canMarkAttendance($date, $student1->id));
        $this->assertTrue(CalendarEvent::canMarkAttendance($date, $student2->id));
    }

    /**
     * Test 23: Upcoming closures includes weekends and holidays.
     */
    public function test_upcoming_closures_returns_weekends_and_holidays(): void
    {
        // Create a holiday next week
        $nextWeek = Carbon::now()->addDays(3);
        CalendarEvent::factory()->create([
            'event_type' => 'holiday',
            'start_date' => $nextWeek,
            'end_date' => $nextWeek,
            'affects_attendance' => true,
        ]);

        $closures = CalendarEvent::getUpcomingClosures(7);
        // Should include the holiday and any weekend days in the next 7 days
        $this->assertGreaterThan(0, count($closures));
        $foundHoliday = false;
        foreach ($closures as $c) {
            if ($c['date'] == $nextWeek->format('Y-m-d')) {
                $foundHoliday = true;
                $this->assertEquals('holiday', $c['reason']);
            }
        }
        $this->assertTrue($foundHoliday);
    }

    /* ==========================================================================
     * NEGATIVE TESTS (404, etc.)
     * ========================================================================== */

    /**
     * Test 24: View non-existent event returns 404.
     */
    public function test_view_non_existent_event_returns_404(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
                         ->getJson('/api/calendar-events/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Calendar event not found',
                 ]);
    }

    /**
     * Test 25: Update non-existent event returns 404.
     */
    public function test_update_non_existent_event_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->putJson('/api/calendar-events/99999', ['title' => 'New'])
             ->assertStatus(404);
    }

    /**
     * Test 26: Delete non-existent event returns 404.
     */
    public function test_delete_non_existent_event_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->deleteJson('/api/calendar-events/99999')
             ->assertStatus(404);
    }

    /**
     * Test 27: Check date endpoint with invalid date format.
     */
    public function test_check_date_invalid_format_returns_422(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
             ->getJson('/api/calendar-events/check-date/invalid')
             ->assertStatus(422); // date parsing will fail (Carbon throws, but likely caught by Laravel validation)
    }
}
