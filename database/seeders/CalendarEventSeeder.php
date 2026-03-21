<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\Carbon;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@school.com')->first();

        if (!$admin) {
            $admin = User::first();
        }

        $year = Carbon::now()->year;
        $events = [
            // Holidays
            [
                'title' => 'Ethiopian New Year',
                'description' => 'Enkutatash - Ethiopian New Year celebration',
                'event_type' => 'holiday',
                'start_date' => $year . '-09-11',
                'end_date' => $year . '-09-12',
                'is_recurring' => true,
                'recurring_pattern' => 'yearly',
                'affects_attendance' => true,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Meskel Festival',
                'description' => 'Finding of the True Cross',
                'event_type' => 'holiday',
                'start_date' => $year . '-09-27',
                'end_date' => $year . '-09-27',
                'is_recurring' => true,
                'recurring_pattern' => 'yearly',
                'affects_attendance' => true,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Christmas',
                'description' => 'Ethiopian Christmas (Genna)',
                'event_type' => 'holiday',
                'start_date' => ($year + 1) . '-01-07',
                'end_date' => ($year + 1) . '-01-07',
                'is_recurring' => true,
                'recurring_pattern' => 'yearly',
                'affects_attendance' => true,
                'created_by' => $admin->id,
            ],

            // Exams
            [
                'title' => 'Mid-Term Examinations',
                'description' => 'First semester mid-term exams',
                'event_type' => 'exam',
                'start_date' => $year . '-10-15',
                'end_date' => $year . '-10-20',
                'is_recurring' => false,
                'affects_attendance' => false,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Final Examinations',
                'description' => 'End of year exams',
                'event_type' => 'exam',
                'start_date' => $year . '-12-01',
                'end_date' => $year . '-12-15',
                'is_recurring' => false,
                'affects_attendance' => false,
                'created_by' => $admin->id,
            ],

            // Events
            [
                'title' => 'Sports Day',
                'description' => 'Annual sports competition',
                'event_type' => 'event',
                'start_date' => $year . '-11-05',
                'end_date' => $year . '-11-05',
                'is_recurring' => true,
                'recurring_pattern' => 'yearly',
                'affects_attendance' => true,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Parent-Teacher Conference',
                'description' => 'Meet with parents to discuss student progress',
                'event_type' => 'event',
                'start_date' => $year . '-10-25',
                'end_date' => $year . '-10-25',
                'is_recurring' => false,
                'affects_attendance' => false,
                'created_by' => $admin->id,
            ],

            // Grade-specific events
            [
                'title' => 'Grade 12 Mock Exams',
                'description' => 'Preparation for national exams',
                'event_type' => 'exam',
                'start_date' => $year . '-03-10',
                'end_date' => $year . '-03-15',
                'is_recurring' => false,
                'affects_attendance' => false,
                'applicable_grades' => '4', // Grade 12
                'created_by' => $admin->id,
            ],
        ];

        $count = 0;
        foreach ($events as $event) {
            CalendarEvent::firstOrCreate(
                [
                    'title' => $event['title'],
                    'start_date' => $event['start_date'],
                ],
                $event
            );
            $count++;
        }

        $this->command->info("✅ $count calendar events seeded successfully!");
    }
}
