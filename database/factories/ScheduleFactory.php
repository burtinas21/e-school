<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        // Create a grade first
        $grade = Grade::factory()->create();

        // Subject and section must belong to the same grade
        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        $dayOfWeek = $this->faker->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);

        return [
            'grade_id'    => $grade->id,
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'teacher_id'  => $teacher->id,
            'period_id'   => $period->id,
            'day_of_week' => $dayOfWeek,
            'is_active'   => true,
        ];
    }

    /**
     * Assign to a specific section.
     */
    public function forSection(Section $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section_id' => $section->id,
            'grade_id'   => $section->grade_id,
        ]);
    }

    /**
     * Assign to a specific teacher.
     */
    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * Set a specific day.
     */
    public function onDay(string $day): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
        ]);
    }

    /**
     * Set a specific period.
     */
    public function atPeriod(Period $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period_id' => $period->id,
        ]);
    }

    /**
     * Mark schedule as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
