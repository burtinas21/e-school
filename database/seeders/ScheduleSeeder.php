<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\Period;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $sections = Section::with('grade')->get();
        $periods = Period::where('is_break', false)->orderBy('period_number')->get();

        $createdCount = 0;

        foreach ($sections as $section) {
            // Get all teacher assignments for this section
            $assignments = TeacherAssignment::where('section_id', $section->id)
                ->with(['teacher', 'subject'])
                ->get();

            if ($assignments->count() == 0) {
                continue;
            }

            foreach ($days as $day) {
                // Create a schedule for each period
                $periodIndex = 0;
                foreach ($periods as $period) {
                    // Cycle through assignments
                    $assignmentIndex = $periodIndex % $assignments->count();
                    $assignment = $assignments[$assignmentIndex];

                    Schedule::firstOrCreate(
                        [
                            'section_id' => $section->id,
                            'day_of_week' => $day,
                            'period_id' => $period->id,
                        ],
                        [
                            'grade_id' => $section->grade_id,
                            'subject_id' => $assignment->subject_id,
                            'teacher_id' => $assignment->teacher_id,
                            'is_active' => true,
                        ]
                    );

                    $createdCount++;
                    $periodIndex++;
                }
            }
        }

        $this->command->info("✅ $createdCount schedule entries seeded successfully!");
    }
}
