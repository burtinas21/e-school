<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [
            // Morning classes
            [
                'name' => 'Period 1',
                'period_number' => 1,
                'start_time' => '08:00:00',
                'end_time' => '08:45:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Period 2',
                'period_number' => 2,
                'start_time' => '08:45:00',
                'end_time' => '09:30:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Period 3',
                'period_number' => 3,
                'start_time' => '09:30:00',
                'end_time' => '10:15:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
            // Morning break
            [
                'name' => 'Morning Break',
                'period_number' => 4,
                'start_time' => '10:15:00',
                'end_time' => '10:30:00',
                'is_break' => true,
                'break_name' => 'Morning Break',
                'is_active' => true,
            ],
            // Late morning classes
            [
                'name' => 'Period 4',
                'period_number' => 5,
                'start_time' => '10:30:00',
                'end_time' => '11:15:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Period 5',
                'period_number' => 6,
                'start_time' => '11:15:00',
                'end_time' => '12:00:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
            // Lunch break
            [
                'name' => 'Lunch',
                'period_number' => 7,
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'is_break' => true,
                'break_name' => 'Lunch Break',
                'is_active' => true,
            ],
            // Afternoon classes
            [
                'name' => 'Period 6',
                'period_number' => 8,
                'start_time' => '13:00:00',
                'end_time' => '13:45:00',
                'is_break' => false,
                'break_name' => null,
                'is_active' => true,
            ],
        ];

        foreach ($periods as $period) {
            Period::firstOrCreate(
                ['period_number' => $period['period_number']],
                $period
            );
        }

        $this->command->info('✅ Periods seeded successfully!');
    }
}
