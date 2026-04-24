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
            SettingSeeder::class,    // New: Defaults for school
            AdminUserSeeder::class,  // New: Admin account
            GradeSeeder::class,
            PeriodSeeder::class,
            SectionSeeder::class,
            SubjectSeeder::class,
            UserSeeder::class,
            GuardianSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,    // Ensure student seeder is here
            TeacherAssignmentSeeder::class,
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            NotificationSeeder::class,
            CalendarEventSeeder::class,
        ]);
    }
}
