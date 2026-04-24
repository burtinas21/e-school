<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'school_name' => 'E-School International',
            'academic_year' => '2024-2025',
            'school_email' => 'admin@e-school.edu',
            'school_phone' => '+1 234 567 890',
            'school_address' => '123 Education St, Knowledge City',
            'passing_percentage' => '75',
            'working_days' => '200',
            'allow_weekend_marking' => false,
            'attendance_policy' => 'Attendance should be marked within the first 10 minutes of every period. Guardians get notified on all Absences.',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        $this->command->info('Default School Settings seeded successfully!');
    }
}
