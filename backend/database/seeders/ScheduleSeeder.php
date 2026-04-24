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
                    // Try to find an assignment where the teacher is available
                    $assignment = null;
                    $availableAssignments = $assignments->shuffle(); // Randomize to vary schedules

                    foreach ($availableAssignments as $candidate) {
                        $isBusy = Schedule::where('teacher_id', $candidate->teacher_id)
                            ->where('day_of_week', $day)
                            ->where('period_id', $period->id)
                            ->exists();

                        if (!$isBusy) {
                            $assignment = $candidate;
                            break;
                        }
                    }

                    // If no teacher is available from the section's assignments, skip this period
                    if (!$assignment) {
                        continue;
                    }

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
                }
            }
        }

        $this->command->info("✅ $createdCount schedule entries seeded successfully!");
    }
}
