<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugGradeTest extends TestCase
{
    use RefreshDatabase; // ✅ This runs migrations for tests

    public function test_debug_grade_routes(): void
    {
        // First, run migrations (RefreshDatabase does this automatically)

        // Create a test grade
        $grade = Grade::factory()->create();

        // Test GET all grades
        $response = $this->get('/api/grades');
        echo "\nGET /api/grades - Status: " . $response->status();

        // Test GET single grade
        $response = $this->get('/api/grades/' . $grade->id);
        echo "\nGET /api/grades/{id} - Status: " . $response->status();

        // Test POST (create) - should fail without auth
        $response = $this->postJson('/api/grades', [
            'name' => 'Test Grade',
            'level' => 9
        ]);
        echo "\nPOST /api/grades - Status: " . $response->status();

        $this->assertTrue(true);
    }
}
