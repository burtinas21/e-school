<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Grade;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all grades
        $grades = Grade::all();

        foreach ($grades as $grade) {
            // Create sections A, B, C for each grade
            $sections = ['A', 'B', 'C'];

            foreach ($sections as $sectionName) {
                Section::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'name' => $sectionName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Sections seeded successfully!');
    }
}
