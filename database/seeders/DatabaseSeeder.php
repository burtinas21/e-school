<?php

namespace Database\Seeders;

//use App\Models\User;
use App\Models\TeacherAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
          $this->call([
            RoleSeeder::class,
            GradeSeeder::class,
            PeriodSeeder::class,
            SectionSeeder::class,
            SubjectSeeder::class,
            UserSeeder::class,  // depend on level 1
            // level 3: Depends on level 2

            GuardianSeeder::class,
            TeacherSeeder::class,

            // level 4: depends on level 1& 2

            SectionSeeder::class,
            TeacherAssignmentSeeder::class,


            // level 6 : depends on level 5

            ScheduleSeeder::class,
            AttendanceSeeder::class,
            NotificationSeeder::class,

            // level 7: depends on level 2

            CalendarEventSeeder::class,


          ]);
    }
}
