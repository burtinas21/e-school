<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            // Elementary/Middle School
            ['name' => 'Grade 1', 'level' => 1, 'is_active' => true],
            ['name' => 'Grade 2', 'level' => 2, 'is_active' => true],
            ['name' => 'Grade 3', 'level' => 3, 'is_active' => true],
            ['name' => 'Grade 4', 'level' => 4, 'is_active' => true],
            ['name' => 'Grade 5', 'level' => 5, 'is_active' => true],
            ['name' => 'Grade 6', 'level' => 6, 'is_active' => true],
            ['name' => 'Grade 7', 'level' => 7, 'is_active' => true],
            ['name' => 'Grade 8', 'level' => 8, 'is_active' => true],

            // High School
            ['name' => 'Grade 9', 'level' => 9, 'is_active' => true],
            ['name' => 'Grade 10', 'level' => 10, 'is_active' => true],
            ['name' => 'Grade 11', 'level' => 11, 'is_active' => true],
            ['name' => 'Grade 12', 'level' => 12, 'is_active' => true],
        ];

        foreach ($grades as $grade) {
            Grade::firstOrCreate(
                ['name' => $grade['name']], // Check by name
                $grade // Create with these values if not exists
            );
        }

        $this->command->info('✅ Grades 1-12 seeded successfully!');
    }
}
