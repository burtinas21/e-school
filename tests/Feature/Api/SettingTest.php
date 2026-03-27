<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
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

        // Create admin user
        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'email'   => 'admin@test.com',
        ]);

        // Create regular user (student)
        $this->regularUser = User::factory()->create([
            'role_id' => 3,
            'email'   => 'user@test.com',
        ]);
    }

    /* ==========================================================================
     * 1. AUTHENTICATION & AUTHORIZATION
     * ========================================================================== */

    /**
     * Test 1: Unauthenticated user cannot access any settings endpoints.
     */
    public function test_unauthenticated_cannot_access_settings(): void
    {
        $this->getJson('/api/settings')->assertStatus(401);
        $this->getJson('/api/settings/school_name')->assertStatus(401);
        $this->putJson('/api/settings', [])->assertStatus(401);
    }

    /**
     * Test 2: Non‑admin user cannot list settings.
     */
    public function test_non_admin_cannot_list_settings(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
             ->getJson('/api/settings')
             ->assertStatus(403);
    }

    /**
     * Test 3: Non‑admin user cannot view a single setting.
     */
    public function test_non_admin_cannot_view_setting(): void
    {
        Setting::set('school_name', 'Test School');
        $this->actingAs($this->regularUser, 'sanctum')
             ->getJson('/api/settings/school_name')
             ->assertStatus(403);
    }

    /**
     * Test 4: Non‑admin user cannot update settings.
     */
    public function test_non_admin_cannot_update_settings(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
             ->putJson('/api/settings', ['settings' => [['key' => 'school_name', 'value' => 'Hacked']]])
             ->assertStatus(403);
    }

    /* ==========================================================================
     * 2. ADMIN ACCESS – LIST & VIEW
     * ========================================================================== */

    /**
     * Test 5: Admin can list settings (empty initially).
     */
    public function test_admin_can_list_settings(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/settings');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(0, 'data');
    }

    /**
     * Test 6: Admin can view a specific setting.
     */
    public function test_admin_can_view_setting(): void
    {
        Setting::set('school_name', 'My School');
        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->getJson('/api/settings/school_name');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'key'   => 'school_name',
                         'value' => 'My School',
                     ]
                 ]);
    }

    /* ==========================================================================
     * 3. ADMIN ACCESS – UPDATE (CREATE & UPDATE)
     * ========================================================================== */

    /**
     * Test 7: Admin can create new settings via bulk update.
     */
    public function test_admin_can_create_settings(): void
    {
        $settings = [
            ['key' => 'school_name', 'value' => 'My School'],
            ['key' => 'max_absences', 'value' => 5],
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/settings', ['settings' => $settings]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => '2 settings updated',
                 ]);

        $this->assertEquals('My School', Setting::get('school_name'));
        $this->assertEquals(5, Setting::get('max_absences'));
    }

    /**
     * Test 8: Admin can update existing settings.
     */
    public function test_admin_can_update_settings(): void
    {
        Setting::set('school_name', 'Old Name');
        $settings = [['key' => 'school_name', 'value' => 'New Name']];

        $response = $this->actingAs($this->adminUser, 'sanctum')
                         ->putJson('/api/settings', ['settings' => $settings]);

        $response->assertStatus(200);
        $this->assertEquals('New Name', Setting::get('school_name'));
    }

    /* ==========================================================================
     * 4. VALIDATION
     * ========================================================================== */

    /**
     * Test 9: Update requires the settings array.
     */
    public function test_update_validation_requires_settings_array(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->putJson('/api/settings', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['settings']);
    }

    /**
     * Test 10: Each setting item must have a key.
     */
    public function test_update_validation_requires_key(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->putJson('/api/settings', ['settings' => [['value' => 'test']]])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['settings.0.key']);
    }

    /* ==========================================================================
     * 5. 404 HANDLING
     * ========================================================================== */

    /**
     * Test 11: View non‑existent setting returns 404.
     */
    public function test_view_nonexistent_setting_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
             ->getJson('/api/settings/unknown_key')
             ->assertStatus(404)
             ->assertJson(['message' => 'Setting not found']);
    }

    /* ==========================================================================
     * 6. MODEL HELPER TESTS (optional but useful)
     * ========================================================================== */

    /**
     * Test 12: Model helper `get` returns correct value.
     */
    public function test_model_helper_get(): void
    {
        Setting::set('test_key', 'test_value');
        $this->assertEquals('test_value', Setting::get('test_key'));
        $this->assertEquals('default', Setting::get('missing', 'default'));
    }

    /**
     * Test 13: Model helper `set` updates existing value.
     */
    public function test_model_helper_set(): void
    {
        Setting::set('test_key', 'first');
        $this->assertEquals('first', Setting::get('test_key'));

        Setting::set('test_key', 'second');
        $this->assertEquals('second', Setting::get('test_key'));
        $this->assertCount(1, Setting::all()); // only one record
    }
}
