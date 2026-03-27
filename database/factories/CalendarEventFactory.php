<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+3 months');
        $endDate = $this->faker->dateTimeBetween($startDate, Carbon::parse($startDate)->addDays(rand(1, 7)));

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'event_type' => $this->faker->randomElement(['holiday', 'exam', 'event', 'closure']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'is_recurring' => $this->faker->boolean(20), // 20% chance recurring
            'recurring_pattern' => function (array $attributes) {
                return $attributes['is_recurring'] ? $this->faker->randomElement(['yearly', 'monthly', 'weekly']) : null;
            },
            'affects_attendance' => $this->faker->boolean(80),
            'applicable_grades' => null, // optional, can be set via states
            'applicable_sections' => null, // optional
            'created_by' => User::factory(),
        ];
    }

    /**
     * Set event type to holiday.
     */
    public function holiday(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'holiday',
            'affects_attendance' => true,
        ]);
    }

    /**
     * Set event type to exam.
     */
    public function exam(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'exam',
            'affects_attendance' => false,
        ]);
    }

    /**
     * Set event as recurring with a specific pattern.
     */
    public function recurring(string $pattern = 'yearly'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_pattern' => $pattern,
        ]);
    }

    /**
     * Set event to affect attendance.
     */
    public function affectsAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'affects_attendance' => true,
        ]);
    }

    /**
     * Set event to apply only to specific grade IDs (comma-separated).
     */
    public function forGrades(array $gradeIds): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_grades' => implode(',', $gradeIds),
        ]);
    }

    /**
     * Set event to apply only to specific section IDs (comma-separated).
     */
    public function forSections(array $sectionIds): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_sections' => implode(',', $sectionIds),
        ]);
    }

    /**
     * Set a specific start and end date.
     */
    public function onDate($date): static
    {
        $date = Carbon::parse($date);
        return $this->state(fn (array $attributes) => [
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
        ]);
    }

    /**
     * Set a date range.
     */
    public function between($start, $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => Carbon::parse($start)->toDateString(),
            'end_date' => Carbon::parse($end)->toDateString(),
        ]);
    }
}

