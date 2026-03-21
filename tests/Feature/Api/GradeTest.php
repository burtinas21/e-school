<?php

namespace Tests\Feature\Api;

use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Log;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    protected $adminToken;
    protected $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        //===== create roles first ======
    \App\Models\Role::create(['name' => 'Admin']);
    \App\Models\Role::create(['name' => 'Teacher']);
    \App\Models\Role::create(['name' => 'Student']);
    \App\Models\Role::create(['name' => 'Guardian']);


        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);
        // debug : verify role_id

        \Log::info('Admin role_id: ' . $admin->role_id);

        $this->adminToken = $admin->createToken('admin-token')->plainTextToken;

        // Create regular user (student)
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'is_active' => true,
        ]);
        $this->userToken = $user->createToken('user-token')->plainTextToken;
    }

    /**
     * Test 1: Anyone can list all grades (public)
     */
    public function test_anyone_can_list_grades(): void
    {
        // Create grades
        Grade::factory()->count(3)->create();

        // Without auth - should work
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'name', 'level', 'is_active']
                         ]
                     ]
                 ]);

        // With auth - should also work
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/grades');
        $response->assertStatus(200);
    }

    /**
     * Test 2: Anyone can view a single grade (public)
     */
    public function test_anyone_can_view_grade(): void
    {
        $grade = Grade::factory()->create();

        // Without auth
        $response = $this->getJson('/api/grades/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $grade->id,
                         'name' => $grade->name,
                     ]
                 ]);

        // With auth
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/grades/' . $grade->id);
        $response->assertStatus(200);
    }

    /**
     * Test 3: Anyone can view active grades (public)
     */
    public function test_anyone_can_view_active_grades(): void
    {
        Grade::factory()->active()->count(3)->create();
        Grade::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/grades/active');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 4: Anyone can view grade sections (public)
     */
    public function test_anyone_can_view_grade_sections(): void
    {
        $grade = Grade::factory()->create();

        //==== CREATE sSections manually to avoid duplicate issues ======//
        Section::create(['grade_id' => $grade->id, 'name'=> 'A', 'is_active'=>true]);
        Section::create(['grade_id'=>$grade->id, 'name'=>'B', 'is_active'=>true]);
        Section::create(['grade_id' => $grade->id, 'name'=>'C', 'is_active' => true]);

        // Create sections for this grade   // are temporary codes  and i create sections manully here  to avoid the duplication of section
        //Section::factory()->count(3)->create(['grade_id' => $grade->id]);

        $response = $this->getJson('/api/grades/' . $grade->id . '/sections');
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Test 5: Only admin can create a grade
     */
    public function test_only_admin_can_create_grade(): void
    {
        $gradeData = [
            'name' => 'Grade 8',
            'level' => 8,
            'is_active' => true,
        ];

        // Without auth - should fail
        $response = $this->postJson('/api/grades', $gradeData);
        $response->assertStatus(401);

        // With regular user - should fail (403 Forbidden)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/grades', $gradeData);
        $response->assertStatus(403);

        // With admin - should succeed
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/grades', $gradeData);
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Grade 8',
                         'level' => 8,
                         'is_active' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('grades', [
            'name' => 'Grade 8',
            'level' => 8,
        ]);
    }

    /**
     * Test 6: Only admin can update a grade
     */
    public function test_only_admin_can_update_grade(): void
    {
        $grade = Grade::factory()->create(['name' => 'Old Name']);

        $updateData = ['name' => 'Updated Grade Name'];

        /** With regular user - should fail
         *
          */
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/api/grades/' . $grade->id, $updateData);
        $response->assertStatus(403);

        /** With admin - should succeed
         **/
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/grades/' . $grade->id, $updateData);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $grade->id,
                         'name' => 'Updated Grade Name',
                     ]
                 ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'name' => 'Updated Grade Name',
        ]);
    }

    /**
     * Test 7: Only admin can delete a grade
     */
    public function test_only_admin_can_delete_grade(): void
    {
        $grade = Grade::factory()->create();

        // With regular user - should fail
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/api/grades/' . $grade->id);
        $response->assertStatus(403);

        // With admin - should succeed
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/grades/' . $grade->id);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('grades', [
            'id' => $grade->id,
        ]);
    }

    /**
     * Test 8: Cannot delete grade that has sections
     * business rule...
     */
    public function test_cannot_delete_grade_with_sections(): void
    {
        $grade = Grade::factory()->create();

        // Create sections for this grade
       // Section::factory()->count(2)->create(['grade_id' => $grade->id]);

       /**
        * create sections manually  here also like test 4::========//
       */
        Section::create(['grade_id' => $grade->id, 'name'=> 'A', 'is_active'=>true]);
        Section::create(['grade_id'=>$grade->id, 'name'=>'B', 'is_active'=>true]);
        Section::create(['grade_id' => $grade->id, 'name'=>'C', 'is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/grades/' . $grade->id);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                 ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
        ]);
    }

    /**
     * Test 9: Validation - name is required
     */
    public function test_grade_requires_name(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/grades', [
            'level' => 9,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 10: Validation - name must be unique
     */
    public function test_grade_name_must_be_unique(): void
    {
        Grade::factory()->create(['name' => 'Grade 9']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/grades', [
            'name' => 'Grade 9',
            'level' => 9,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 11: Filter grades by level
     */
    public function test_filter_grades_by_level(): void
    {
        Grade::factory()->level(9)->count(2)->create();
        Grade::factory()->level(10)->count(3)->create();

        $response = $this->getJson('/api/grades?level=9');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 12: Search grades by name
     */
    public function test_search_grades(): void
    {
        Grade::factory()->create(['name' => 'Grade 9 Science']);
        Grade::factory()->create(['name' => 'Grade 9 Math']);
        Grade::factory()->create(['name' => 'Grade 10 Science']);

        $response = $this->getJson('/api/grades?search=Science');
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test 13: Pagination works
     */
    public function test_grades_pagination(): void
    {
        Grade::factory()->count(25)->create();

        $response = $this->getJson('/api/grades?per_page=10');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'current_page',
                         'data',
                         'per_page',
                         'total',
                     ]
                 ]);

        $this->assertEquals(10, $response['data']['per_page']);
    }

    /**
     * Test 14: View grade not found returns 404
     */
    public function test_view_non_existent_grade_returns_404(): void
    {
        $response = $this->getJson('/api/grades/99999');
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Grade not found',
                 ]);
    }
}
