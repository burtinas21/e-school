<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Schedule;
//use App\Models\Teacher;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::with(['section', 'grade'])->get();
        $today = Carbon::today();
        $createdCount = 0;

        // Create attendance for last 14 school days (2 weeks)
        for ($i = 1; $i <= 14; $i++) {
            $date = $today->copy()->subDays($i);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Get schedules for this day of week
            $dayName = $date->format('l');
            $schedules = Schedule::where('day_of_week', $dayName)
                ->with(['section', 'subject', 'teacher', 'period'])
                ->get()
                ->groupBy('section_id');

            foreach ($schedules as $sectionId => $sectionSchedules) {
                $sectionStudents = $students->filter(function($student) use ($sectionId) {
                    return $student->section_id == $sectionId;
                });

                foreach ($sectionSchedules as $schedule) {
                    foreach ($sectionStudents as $student) {
                        // Random attendance status (80% present, 10% absent, 5% late, 5% permission)
                        $rand = rand(1, 100);
                        $status = 'present';

                        if ($rand > 80 && $rand <= 90) {
                            $status = 'absent';
                        } elseif ($rand > 90 && $rand <= 95) {
                            $status = 'late';
                        } elseif ($rand > 95) {
                            $status = 'permission';
                        }

                        Attendance::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'subject_id' => $schedule->subject_id,
                                'date' => $date->format('Y-m-d'),
                                'period_id' => $schedule->period_id,
                            ],
                            [
                                'teacher_id' => $schedule->teacher_id,
                                'grade_id' => $student->grade_id,
                                'section_id' => $sectionId,
                                'status' => $status,
                            ]
                        );

                        $createdCount++;
                    }
                }
            }
        }

        $this->command->info("✅ $createdCount attendance records seeded successfully!");
    }
}
