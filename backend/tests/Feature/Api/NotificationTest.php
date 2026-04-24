<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teacherUser;
    protected User $guardianUser;
    protected User $studentUser;
    protected Guardian $guardian;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed=false;

        // Create roles
        \App\Models\Role::create(['name' => 'Admin']);
        \App\Models\Role::create(['name' => 'Teacher']);
        \App\Models\Role::create(['name' => 'Student']);
        \App\Models\Role::create(['name' => 'Guardian']);

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role_id' => 1, 'email' => 'admin@test.com']);
        $this->teacherUser = User::factory()->create(['role_id' => 2, 'email' => 'teacher@test.com']);
        $this->guardianUser = User::factory()->create(['role_id' => 4, 'email' => 'guardian@test.com']);
        $this->studentUser = User::factory()->create(['role_id' => 3, 'email' => 'student@test.com']);

        // Create guardian profile linked to the guardian user
        $this->guardian = Guardian::factory()->create(['user_id' => $this->guardianUser->id]);

        // Create student profile linked to the guardian
        $this->student = Student::factory()->create([
            'user_id'     => $this->studentUser->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }

    /* ==========================================================================
     * 1. AUTHENTICATION TESTS
     * ========================================================================== */

    /**
     * Test 1: Unauthenticated users cannot access any notification endpoints.
     */
    public function test_unauthenticated_cannot_access_any_notification_endpoints(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
        $this->postJson('/api/notifications', [])->assertStatus(401);
        $this->postJson('/api/notifications/bulk', [])->assertStatus(401);
        $this->getJson('/api/notifications/student/1')->assertStatus(401);
        $this->deleteJson('/api/notifications/1')->assertStatus(401);
        $this->putJson('/api/notifications/1/read')->assertStatus(401);
        $this->putJson('/api/notifications/read-all')->assertStatus(401);
    }

    /* ==========================================================================
     * 2. GUARDIAN ACCESS TESTS
     * ========================================================================== */

    /**
     * Test 2: Guardian can list their own notifications.
     */
    public function test_guardian_can_list_their_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data.data')
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'title', 'message', 'type', 'sent_at', 'read_at', 'student']
                         ]
                     ]
                 ]);
    }

    /**
     * Test 3: Guardian can get unread count of their notifications.
     */
    public function test_guardian_can_get_unread_count(): void
    {
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => now(),
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['unread_count' => 2]
                 ]);
    }

    /**
     * Test 4: Guardian can mark a single notification as read.
     */
    public function test_guardian_can_mark_single_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification marked as read',
                 ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test 5: Guardian can mark all their notifications as read.
     */
    public function test_guardian_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->putJson('/api/notifications/read-all');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'All notifications marked as read',
                 ]);

        $this->assertEquals(0, Notification::whereNull('read_at')->count());
    }

    /**
     * Test 6: Guardian cannot access notifications belonging to another guardian.
     */
    public function test_guardian_cannot_access_notifications_of_another_guardian(): void
    {
        $anotherGuardian = Guardian::factory()->create();
        $anotherStudent = Student::factory()->create(['guardian_id' => $anotherGuardian->id]);
        $notification = Notification::factory()->create([
            'guardian_id' => $anotherGuardian->id,
            'student_id'  => $anotherStudent->id,
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404); // not found because it doesn't belong to this guardian
    }

    /* ==========================================================================
     * 3. STUDENT ACCESS TESTS
     * ========================================================================== */

    /**
     * Test 7: Student can list their own notifications (read‑only).
     */
    public function test_student_can_list_their_own_notifications(): void
    {
        // Notifications about this student
        Notification::factory()->count(2)->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);
        // Another student's notifications (should not appear)
        $otherStudent = Student::factory()->create();
        Notification::factory()->create([
            'student_id'  => $otherStudent->id,
            'guardian_id' => Guardian::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 8: Student cannot get unread count (guardians only).
     */
    public function test_student_cannot_get_unread_count(): void
    {
        $this->actingAs($this->studentUser, 'sanctum')
             ->getJson('/api/notifications/unread-count')
             ->assertStatus(403)
             ->assertJson(['message' => 'Only guardians can access unread count']);
    }

    /**
     * Test 9: Student cannot mark a notification as read.
     */
    public function test_student_cannot_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'read_at'     => null,
        ]);

        $this->actingAs($this->studentUser, 'sanctum')
             ->putJson("/api/notifications/{$notification->id}/read")
             ->assertStatus(403)
             ->assertJson(['message' => 'Only guardians can mark notifications as read']);
    }

    /**
     * Test 10: Student cannot mark all notifications as read.
     */
    public function test_student_cannot_mark_all_notifications_as_read(): void
    {
        $this->actingAs($this->studentUser, 'sanctum')
             ->putJson('/api/notifications/read-all')
             ->assertStatus(403)
             ->assertJson(['message' => 'Only guardians can mark notifications as read']);
    }

    /* ==========================================================================
     * 4. CREATE NOTIFICATION (ADMIN/TEACHER ONLY)
     * ========================================================================== */

    /**
     * Test 11: Admin can create a notification.
     */
    public function test_admin_can_create_notification(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'type'       => 'absence',
            'title'      => 'Absence Alert',
            'message'    => 'Your child was absent today.',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/notifications', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification created successfully',
                     'data' => [
                         'student_id'  => $this->student->id,
                         'guardian_id' => $this->guardian->id,
                         'type'        => 'absence',
                     ]
                 ]);

        $this->assertDatabaseHas('notifications', [
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'type'        => 'absence',
        ]);
    }

    /**
     * Test 12: Teacher can create a notification.
     */
    public function test_teacher_can_create_notification(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'type'       => 'late',
            'title'      => 'Late Arrival',
            'message'    => 'Your child arrived late today.',
        ];

        $this->actingAs($this->teacherUser, 'sanctum')
             ->postJson('/api/notifications', $data)
             ->assertStatus(201);

        $this->assertDatabaseHas('notifications', ['title' => 'Late Arrival']);
    }

    /**
     * Test 13: Student cannot create a notification.
     */
    public function test_student_cannot_create_notification(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'type'       => 'absence',
            'title'      => 'Absence Alert',
            'message'    => 'Your child was absent today.',
        ];

        $this->actingAs($this->studentUser, 'sanctum')
             ->postJson('/api/notifications', $data)
             ->assertStatus(403);
    }

    /**
     * Test 14: Create notification validation – required fields.
     */
    public function test_create_notification_validation_required_fields(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/notifications', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['student_id', 'type', 'title', 'message']);
    }

    /**
     * Test 15: Create notification validation – invalid type.
     */
    public function test_create_notification_validation_invalid_type(): void
    {
        $data = [
            'student_id' => $this->student->id,
            'type'       => 'invalid_type',
            'title'      => 'Test',
            'message'    => 'Test message',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/notifications', $data)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test 16: Create notification fails if student has no guardian.
     */
    public function test_create_notification_fails_if_student_has_no_guardian(): void
    {
        $studentWithoutGuardian = Student::factory()->create(['guardian_id' => null]);
        $data = [
            'student_id' => $studentWithoutGuardian->id,
            'type'       => 'absence',
            'title'      => 'Absence Alert',
            'message'    => 'Your child was absent today.',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/notifications', $data)
             ->assertStatus(422)
             ->assertJson(['message' => 'Student has no guardian assigned']);
    }

    /* ==========================================================================
     * 5. BULK NOTIFICATIONS
     * ========================================================================== */

    /**
     * Test 17: Admin can create bulk notifications for a section.
     */
    public function test_admin_can_create_bulk_notifications_for_section(): void
    {
        $section = Section::factory()->create();
        $guardian1 = Guardian::factory()->create();
        $guardian2 = Guardian::factory()->create();
        $student1 = Student::factory()->create(['section_id' => $section->id, 'guardian_id' => $guardian1->id]);
        $student2 = Student::factory()->create(['section_id' => $section->id, 'guardian_id' => $guardian2->id]);
        // student without guardian (should be skipped)
        Student::factory()->create(['section_id' => $section->id, 'guardian_id' => null]);

        $data = [
            'section_id' => $section->id,
            'type'       => 'daily_summary',
            'title'      => 'Daily Summary',
            'message'    => 'Here is the daily summary.',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->postJson('/api/notifications/bulk', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '2 notifications created successfully'
                 ]);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['student_id' => $student1->id]);
        $this->assertDatabaseHas('notifications', ['student_id' => $student2->id]);
    }

    /**
     * Test 18: Teacher can create bulk notifications for a section.
     */
    public function test_teacher_can_create_bulk_notifications(): void
    {
        $section = Section::factory()->create();
        $guardian = Guardian::factory()->create();
        $student = Student::factory()->create(['section_id' => $section->id, 'guardian_id' => $guardian->id]);

        $data = [
            'section_id' => $section->id,
            'type'       => 'warning',
            'title'      => 'Warning',
            'message'    => 'Please check your child\'s performance.',
        ];

        $this->actingAs($this->teacherUser, 'sanctum')
             ->postJson('/api/notifications/bulk', $data)
             ->assertStatus(200);

        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * Test 19: Student cannot create bulk notifications.
     */
    public function test_student_cannot_create_bulk_notifications(): void
    {
        $section = Section::factory()->create();
        $data = [
            'section_id' => $section->id,
            'type'       => 'daily_summary',
            'title'      => 'Daily Summary',
            'message'    => 'Here is the daily summary.',
        ];

        $this->actingAs($this->studentUser, 'sanctum')
             ->postJson('/api/notifications/bulk', $data)
             ->assertStatus(403);
    }

    /**
     * Test 20: Bulk create validation – required fields.
     */
    public function test_bulk_create_validation(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->postJson('/api/notifications/bulk', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['section_id', 'type', 'title', 'message']);
    }

    /* ==========================================================================
     * 6. STUDENT HISTORY (ADMIN/TEACHER ONLY)
     * ========================================================================== */

    /**
     * Test 21: Admin can view notification history for any student.
     */
    public function test_admin_can_view_student_notification_history(): void
    {
        Notification::factory()->count(2)->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson("/api/notifications/student/{$this->student->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test 22: Teacher can view student notification history.
     */
    public function test_teacher_can_view_student_notification_history(): void
    {
        Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $this->actingAs($this->teacherUser, 'sanctum')
             ->getJson("/api/notifications/student/{$this->student->id}")
             ->assertStatus(200);
    }

    /**
     * Test 23: Student cannot view another student's history.
     */
    public function test_student_cannot_view_other_student_history(): void
    {
        $otherStudent = Student::factory()->create();
        $this->actingAs($this->studentUser, 'sanctum')
             ->getJson("/api/notifications/student/{$otherStudent->id}")
             ->assertStatus(403);
    }

    /* ==========================================================================
     * 7. DELETE NOTIFICATION (ADMIN ONLY)
     * ========================================================================== */

    /**
     * Test 24: Admin can delete a notification.
     */
    public function test_admin_can_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification deleted successfully'
                 ]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /**
     * Test 25: Teacher cannot delete a notification.
     */
    public function test_teacher_cannot_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $this->actingAs($this->teacherUser, 'sanctum')
             ->deleteJson("/api/notifications/{$notification->id}")
             ->assertStatus(403);
    }

    /**
     * Test 26: Guardian cannot delete a notification.
     */
    public function test_guardian_cannot_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $this->actingAs($this->guardianUser, 'sanctum')
             ->deleteJson("/api/notifications/{$notification->id}")
             ->assertStatus(403);
    }

    /**
     * Test 27: Delete non‑existent notification returns 404.
     */
    public function test_delete_non_existent_notification_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->deleteJson('/api/notifications/99999')
             ->assertStatus(404)
             ->assertJson(['message' => 'Notification not found']);
    }

    /**
     * Test 28: Mark non‑existent notification as read returns 404.
     */
    public function test_mark_non_existent_notification_as_read_returns_404(): void
    {
        $this->actingAs($this->guardianUser, 'sanctum')
             ->putJson('/api/notifications/99999/read')
             ->assertStatus(404)
             ->assertJson(['message' => 'Notification not found']);
    }

    /* ==========================================================================
     * 8. FILTERING ON INDEX (GUARDIAN ONLY)
     * ========================================================================== */

    /**
     * Test 29: Guardian can filter notifications by read status.
     */
    public function test_guardian_can_filter_notifications_by_read_status(): void
    {
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
        ]);
        Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => now(),
        ]);

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/notifications?filter=unread');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');

        $response = $this->actingAs($this->guardianUser, 'sanctum')
                         ->getJson('/api/notifications?filter=read');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data');
    }

    /**
     * Test 30: Student's filter parameter is ignored (they see all their notifications).
     */
    public function test_student_filter_parameter_is_ignored(): void
    {
        Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'read_at'     => null,
        ]);
        Notification::factory()->create([
            'student_id'  => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'read_at'     => now(),
        ]);

        $response = $this->actingAs($this->studentUser, 'sanctum')
                         ->getJson('/api/notifications?filter=unread');
        // Students can't filter; they see all their notifications (both read and unread)
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /* ==========================================================================
     * 9. MODEL HELPER TESTS
     * ========================================================================== */

    /**
     * Test 31: Notification model helpers (isRead, markAsRead, time_ago) work correctly.
     */
    public function test_notification_model_helpers_work(): void
    {
        $notification = Notification::factory()->create([
            'guardian_id' => $this->guardian->id,
            'student_id'  => $this->student->id,
            'read_at'     => null,
            'sent_at'     => now()->subDay(),
        ]);

        $this->assertFalse($notification->isRead());
        $this->assertNotNull($notification->time_ago);

        $notification->markAsRead();
        $this->assertTrue($notification->fresh()->isRead());
    }
}
